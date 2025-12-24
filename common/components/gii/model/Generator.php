<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\components\gii\model;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\Schema;
use yii\db\TableSchema;
use yiiframe\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Generator extends \yiiframe\gii\Generator
{
    const RELATIONS_NONE = 'none';
    const RELATIONS_ALL = 'all';
    const RELATIONS_ALL_INVERSE = 'all-inverse';

    public $db = 'db';
    public $ns = 'app\models';
    public $tableName;
    public $modelClass;
    public $baseClass = '\common\models\base\BaseModel';
    public $generateRelations = self::RELATIONS_ALL;
    public $generateRelationsFromCurrentSchema = true;
    public $generateLabelsFromComments = true;
    public $useTablePrefix = true;
    public $standardizeCapitals = false;
    public $singularize = false;
    public $useSchemaName = true;
    public $generateQuery = false;
    public $queryNs = 'app\models';
    public $queryClass;
    public $queryBaseClass = 'yii\db\ActiveQuery';
    public $template = 'yiiframe';
    public $enableI18N = true;
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Yii::t('app','模型生成器');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return Yii::t('app','这个生成器为指定的数据库表生成一个ActiveRecord类。');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['db', 'ns', 'tableName', 'modelClass', 'baseClass', 'queryNs', 'queryClass', 'queryBaseClass'], 'filter', 'filter' => 'trim'],
            [['ns', 'queryNs'], 'filter', 'filter' => function ($value) { return trim($value, '\\'); }],

            [['db', 'ns', 'tableName', 'baseClass', 'queryNs', 'queryBaseClass'], 'required'],
            [['db', 'modelClass', 'queryClass'], 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            [['ns', 'baseClass', 'queryNs', 'queryBaseClass'], 'match', 'pattern' => '/^[\w\\\\]+$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['tableName'], 'match', 'pattern' => '/^([\w ]+\.)?([\w\* ]+)$/', 'message' => 'Only word characters, and optionally spaces, an asterisk and/or a dot are allowed.'],
            [['db'], 'validateDb'],
            [['ns', 'queryNs'], 'validateNamespace'],
            [['tableName'], 'validateTableName'],
            [['modelClass'], 'validateModelClass', 'skipOnEmpty' => false],
            [['baseClass'], 'validateClass', 'params' => ['extends' => ActiveRecord::className()]],
            [['queryBaseClass'], 'validateClass', 'params' => ['extends' => ActiveQuery::className()]],
            [['generateRelations'], 'in', 'range' => [self::RELATIONS_NONE, self::RELATIONS_ALL, self::RELATIONS_ALL_INVERSE]],
            [['generateLabelsFromComments', 'useTablePrefix', 'useSchemaName', 'generateQuery', 'generateRelationsFromCurrentSchema'], 'boolean'],
            [['enableI18N', 'standardizeCapitals', 'singularize'], 'boolean'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'ns' => Yii::t('app','名称空间'),
            'db' => Yii::t('app','数据库连接ID'),
            'tableName' => Yii::t('app','表名'),
            'standardizeCapitals' => Yii::t('app','标准化命名'),
            'singularize' => Yii::t('app','奇异化'),
            'modelClass' => Yii::t('app','类名'),
            'baseClass' => Yii::t('app','基类'),
            'generateRelations' => Yii::t('app','生成关系'),
            'generateRelationsFromCurrentSchema' => Yii::t('app','从当前模式生成关系'),
            'generateLabelsFromComments' => Yii::t('app','从DB注释生成标签'),
            'generateQuery' => Yii::t('app','生成ActiveQuery'),
            'queryNs' => Yii::t('app','ActiveQuery名称空间'),
            'queryClass' => Yii::t('app','ActiveQuery类'),
            'queryBaseClass' => Yii::t('app','ActiveQuery基类'),
            'useSchemaName' => Yii::t('app','使用模式名称'),
            'useTablePrefix' => Yii::t('app','使用表前缀'),

            
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'ns' => Yii::t('app','这是要生成的ActiveRecord类的命名空间，例如，<code>app\models</code>'),
            'db' => Yii::t('app','这是DB应用程序组件的ID。'),
            'tableName' => Yii::t('app','这是与新的ActiveRecord类相关联的DB表的名称，例如post。表名可以由数据库模式部分组成，如<code>public.post</code>。表名可以以星号结尾以匹配多个表名，例如<code>tbl_*</code>将匹配名称以<code>tbl_</code>开头的表。在这种情况下，将生成多个ActiveRecord类，每个匹配的表名对应一个;类名将由匹配的字符生成。例如，表<code>tbl_post</code>将生成<code>Post</code>类。'),
            'modelClass' => Yii::t('app','这是要生成的ActiveRecord类的名称。类名不应该包含命名空间部分，因为它在“namespace”中指定。如果“TableName”以星号结尾，则不需要指定类名，在这种情况下将生成多个ActiveRecord类。'),
            'standardizeCapitals' => Yii::t('app','这表明生成的类名是否应该具有标准化的大写字母。例如，<code>SOME_TABLE</code>或<code>Other_Table</code>这样的表名将分别具有类名<code>SomeTable</code>和<code>OtherTable</code>。如果不勾选，相同的表将有类名<code>SOMETABLE</code>和<code>OtherTable</code>。'),
            'singularize' => Yii::t('app','这表明生成的类名是否应该被奇异化。例如,像<code>some_tables</code>这样的表名将具有类名<code>SomeTable</code>。'),
            'baseClass' => Yii::t('app','这是新的ActiveRecord类的基类。它应该是一个完全限定的名称空间类名。'),
            'generateRelations' => Yii::t('app','这表明生成器是否应该根据它在数据库中检测到的外键约束生成关系。注意，如果数据库包含太多表，您可能希望取消选中此选项以加速代码生成过程。'),
            'generateRelationsFromCurrentSchema' => Yii::t('app','这表明生成器应该从当前模式还是从所有可用模式生成关系。'),
            'generateLabelsFromComments' => Yii::t('app','这表明生成器是否应该使用相应DB列的注释来生成属性标签。'),
            'useTablePrefix' => Yii::t('app','指示生成的ActiveRecord类返回的表名应该考虑DB连接的<code>tablePrefix</code>设置。例如，如果表名称是<code>tbl_post</code>和<code>tablePrefix=tbl_</code>， ActiveRecord类将返回表名为<code>{{%post}}</code>。'),
            'useSchemaName' => Yii::t('app','这指示是否在自动生成的ActiveRecord类中包含模式名。只能使用非默认模式。'),
            'generateQuery' => Yii::t('app','是否为ActiveRecord类生成ActiveQuery。'),
            'queryNs' => Yii::t('app','这是要生成的ActiveQuery类的命名空间，例如:<code>app\models</code>'),
            'queryClass' => Yii::t('app','这是要生成的ActiveQuery类的名称。类名不应该包含命名空间部分，因为它在“ActiveQuery命名空间”中指定。如果“Table name”以星号结尾，则不需要指定类名，将生成多个ActiveQuery类。'),
            'queryBaseClass' => Yii::t('app','这是新ActiveQuery类的基类。它应该是一个完全限定的名称空间类名。'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function autoCompleteData()
    {
        $db = $this->getDbConnection();
        if ($db !== null) {
            return [
                'tableName' => function () use ($db) {
                    return $db->getSchema()->getTableNames();
                },
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function requiredTemplates()
    {
        // @todo make 'query.php' to be required before 2.1 release
        return ['model.php'/*, 'query.php'*/];
    }

    /**
     * {@inheritdoc}
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['ns', 'db', 'baseClass', 'generateRelations', 'generateLabelsFromComments', 'queryNs', 'queryBaseClass', 'useTablePrefix', 'generateQuery']);
    }

    /**
     * Returns the `tablePrefix` property of the DB connection as specified
     *
     * @return string
     * @since 2.0.5
     * @see getDbConnection
     */
    public function getTablePrefix()
    {
        $db = $this->getDbConnection();
        if ($db !== null) {
            return $db->tablePrefix;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $files = [];
        $relations = $this->generateRelations();
        $db = $this->getDbConnection();
        foreach ($this->getTableNames() as $tableName) {
            // model :
            $modelClassName = $this->generateClassName($tableName);
            $queryClassName = ($this->generateQuery) ? $this->generateQueryClassName($modelClassName) : false;
            $tableRelations = isset($relations[$tableName]) ? $relations[$tableName] : [];
            $tableSchema = $db->getTableSchema($tableName);
            $params = [
                'tableName' => $tableName,
                'className' => $modelClassName,
                'queryClassName' => $queryClassName,
                'tableSchema' => $tableSchema,
                'properties' => $this->generateProperties($tableSchema),
                'labels' => $this->generateLabels($tableSchema),
                'rules' => $this->generateRules($tableSchema),
                'relations' => $tableRelations,
                'relationsClassHints' => $this->generateRelationsClassHints($tableRelations, $this->generateQuery),
            ];
            $files[] = new CodeFile(
                Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $modelClassName . '.php',
                $this->render('model.php', $params)
            );

            // query :
            if ($queryClassName) {
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $modelClassName;
                $files[] = new CodeFile(
                    Yii::getAlias('@' . str_replace('\\', '/', $this->queryNs)) . '/' . $queryClassName . '.php',
                    $this->render('query.php', $params)
                );
            }
        }

        return $files;
    }

    /**
     * Generates the properties for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated properties (property => type)
     * @since 2.0.6
     */
    protected function generateProperties($table)
    {
        $properties = [];
        foreach ($table->columns as $column) {
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_TINYINT:
                    $type = 'int';
                    break;
                case Schema::TYPE_BOOLEAN:
                    $type = 'bool';
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $type = 'float';
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                case Schema::TYPE_JSON:
                    $type = 'string';
                    break;
                default:
                    $type = $column->phpType;
            }
            if ($column->allowNull){
                $type .= '|null';
            }
            $properties[$column->name] = [
                'type' => $type,
                'name' => $column->name,
                'comment' => $column->comment,
            ];
        }

        return $properties;
    }

    /**
     * Generates the attribute labels for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated attribute labels (name => label)
     */
    public function generateLabels($table)
    {
        $labels = [];
        foreach ($table->columns as $column) {
            if ($this->generateLabelsFromComments && !empty($column->comment)) {
                $labels[$column->name] = strpos($column->comment, ':') !== false
                    ? explode(':', $column->comment, 2)[0]
                    : $column->comment;
            } elseif (!strcasecmp($column->name, 'id')) {
                $labels[$column->name] = 'ID';
            } else {
                $label = Inflector::camel2words($column->name);
                if (!empty($label) && substr_compare($label, ' id', -3, 3, true) === 0) {
                    $label = substr($label, 0, -3) . ' ID';
                }
                $labels[$column->name] = $label;
            }
        }

        return $labels;
    }

    /**
     * Generates the relation class hints for the relation methods
     * @param array $relations the relation array for single table
     * @param bool $generateQuery generates ActiveQuery class (for ActiveQuery namespace available)
     * @return array
     * @since 2.1.4
     */
    public function generateRelationsClassHints($relations, $generateQuery){
        $result = [];
        foreach ($relations as $name => $relation){
            // The queryNs options available if generateQuery is active
            if ($generateQuery) {
                $queryClassRealName = '\\' . $this->queryNs . '\\' . $relation[1];
                if (class_exists($queryClassRealName, true) && is_subclass_of($queryClassRealName, '\yii\db\BaseActiveRecord')) {
                    /** @var \yii\db\ActiveQuery $activeQuery */
                    $activeQuery = $queryClassRealName::find();
                    $activeQueryClass = $activeQuery::className();
                    if (strpos($activeQueryClass, $this->ns) === 0){
                        $activeQueryClass = StringHelper::basename($activeQueryClass);
                    }
                    $result[$name] = '\yii\db\ActiveQuery|' . $activeQueryClass;
                } else {
                    $result[$name] = '\yii\db\ActiveQuery|' . (($this->ns === $this->queryNs) ? $relation[1]: '\\' . $this->queryNs . '\\' . $relation[1]) . 'Query';
                }
            } else {
                $result[$name] = '\yii\db\ActiveQuery';
            }
        }
        return $result;
    }

    /**
     * Generates validation rules for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated validation rules
     */
    public function generateRules($table)
    {
        $types = [];
        $lengths = [];
        foreach ($table->columns as $column) {
            if ($column->autoIncrement) {
                continue;
            }
            if (!$column->allowNull && $column->defaultValue === null) {
                $types['required'][] = $column->name;
            }
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_TINYINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                case Schema::TYPE_JSON:
                    $types['safe'][] = $column->name;
                    break;
                default: // strings
                    if ($column->size > 0) {
                        $lengths[$column->size][] = $column->name;
                    } else {
                        $types['string'][] = $column->name;
                    }
            }
        }
        $rules = [];
        $driverName = $this->getDbDriverName();
        foreach ($types as $type => $columns) {
            if ($driverName === 'pgsql' && $type === 'integer') {
                $rules[] = "[['" . implode("', '", $columns) . "'], 'default', 'value' => null]";
            }
            $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
        }
        foreach ($lengths as $length => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], 'string', 'max' => $length]";
        }

        $db = $this->getDbConnection();

        // Unique indexes rules
        try {
            $uniqueIndexes = array_merge($db->getSchema()->findUniqueIndexes($table), [$table->primaryKey]);
            $uniqueIndexes = array_unique($uniqueIndexes, SORT_REGULAR);
            foreach ($uniqueIndexes as $uniqueColumns) {
                // Avoid validating auto incremental columns
                if (!$this->isColumnAutoIncremental($table, $uniqueColumns)) {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount === 1) {
                        $rules[] = "[['" . $uniqueColumns[0] . "'], 'unique']";
                    } elseif ($attributesCount > 1) {
                        $columnsList = implode("', '", $uniqueColumns);
                        $rules[] = "[['$columnsList'], 'unique', 'targetAttribute' => ['$columnsList']]";
                    }
                }
            }
        } catch (NotSupportedException $e) {
            // doesn't support unique indexes information...do nothing
        }

        // Exist rules for foreign keys
        foreach ($table->foreignKeys as $refs) {
            $refTable = $refs[0];
            $refTableSchema = $db->getTableSchema($refTable);
            if ($refTableSchema === null) {
                // Foreign key could point to non-existing table: https://github.com/yiisoft/yii2-gii/issues/34
                continue;
            }
            $refClassName = $this->generateClassName($refTable);
            unset($refs[0]);
            $attributes = implode("', '", array_keys($refs));
            $targetAttributes = [];
            foreach ($refs as $key => $value) {
                $targetAttributes[] = "'$key' => '$value'";
            }
            $targetAttributes = implode(', ', $targetAttributes);
            $rules[] = "[['$attributes'], 'exist', 'skipOnError' => true, 'targetClass' => $refClassName::className(), 'targetAttribute' => [$targetAttributes]]";
        }

        return $rules;
    }

    /**
     * Generates relations using a junction table by adding an extra viaTable().
     * @param \yii\db\TableSchema the table being checked
     * @param array $fks obtained from the checkJunctionTable() method
     * @param array $relations
     * @return array modified $relations
     */
    private function generateManyManyRelations($table, $fks, $relations)
    {
        $db = $this->getDbConnection();

        foreach ($fks as $pair) {
            list($firstKey, $secondKey) = $pair;
            $table0 = $firstKey[0];
            $table1 = $secondKey[0];
            unset($firstKey[0], $secondKey[0]);
            $className0 = $this->generateClassName($table0);
            $className1 = $this->generateClassName($table1);
            $table0Schema = $db->getTableSchema($table0);
            $table1Schema = $db->getTableSchema($table1);

            // @see https://github.com/yiisoft/yii2-gii/issues/166
            if ($table0Schema === null || $table1Schema === null) {
                continue;
            }

            $link = $this->generateRelationLink(array_flip($secondKey));
            $viaLink = $this->generateRelationLink($firstKey);
            $relationName = $this->generateRelationName($relations, $table0Schema, key($secondKey), true);
            $relations[$table0Schema->fullName][$relationName] = [
                "return \$this->hasMany($className1::className(), $link)->viaTable('"
                . $this->generateTableName($table->name) . "', $viaLink);",
                $className1,
                true,
            ];

            $link = $this->generateRelationLink(array_flip($firstKey));
            $viaLink = $this->generateRelationLink($secondKey);
            $relationName = $this->generateRelationName($relations, $table1Schema, key($firstKey), true);
            $relations[$table1Schema->fullName][$relationName] = [
                "return \$this->hasMany($className0::className(), $link)->viaTable('"
                . $this->generateTableName($table->name) . "', $viaLink);",
                $className0,
                true,
            ];
        }

        return $relations;
    }

    /**
     * @return string[] all db schema names or an array with a single empty string
     * @throws NotSupportedException
     * @since 2.0.5
     */
    protected function getSchemaNames()
    {
        $db = $this->getDbConnection();

        if ($this->generateRelationsFromCurrentSchema) {
            if ($db->schema->defaultSchema !== null) {
                return [$db->schema->defaultSchema];
            }
            return [''];
        }

        $schema = $db->getSchema();
        if ($schema->hasMethod('getSchemaNames')) { // keep BC to Yii versions < 2.0.4
            try {
                $schemaNames = $schema->getSchemaNames();
            } catch (NotSupportedException $e) {
                // schema names are not supported by schema
            }
        }
        if (!isset($schemaNames)) {
            if (($pos = strpos($this->tableName, '.')) !== false) {
                $schemaNames = [substr($this->tableName, 0, $pos)];
            } else {
                $schemaNames = [''];
            }
        }
        return $schemaNames;
    }

    /**
     * @return array the generated relation declarations
     */
    protected function generateRelations()
    {
        if ($this->generateRelations === self::RELATIONS_NONE) {
            return [];
        }

        $db = $this->getDbConnection();
        $relations = [];
        $schemaNames = $this->getSchemaNames();
        foreach ($schemaNames as $schemaName) {
            foreach ($db->getSchema()->getTableSchemas($schemaName) as $table) {
                $className = $this->generateClassName($table->fullName);
                foreach ($table->foreignKeys as $refs) {
                    $refTable = $refs[0];
                    $refTableSchema = $db->getTableSchema($refTable);
                    if ($refTableSchema === null) {
                        // Foreign key could point to non-existing table: https://github.com/yiisoft/yii2-gii/issues/34
                        continue;
                    }
                    unset($refs[0]);
                    $fks = array_keys($refs);
                    $refClassName = $this->generateClassName($refTable);

                    // Add relation for this table
                    $link = $this->generateRelationLink(array_flip($refs));
                    $relationName = $this->generateRelationName($relations, $table, $fks[0], false);
                    $relations[$table->fullName][$relationName] = [
                        "return \$this->hasOne($refClassName::className(), $link);",
                        $refClassName,
                        false,
                    ];

                    // Add relation for the referenced table
                    $hasMany = $this->isHasManyRelation($table, $fks);
                    $link = $this->generateRelationLink($refs);
                    $relationName = $this->generateRelationName($relations, $refTableSchema, $className, $hasMany);
                    $relations[$refTableSchema->fullName][$relationName] = [
                        "return \$this->" . ($hasMany ? 'hasMany' : 'hasOne') . "($className::className(), $link);",
                        $className,
                        $hasMany,
                    ];
                }

                if (($junctionFks = $this->checkJunctionTable($table)) === false) {
                    continue;
                }

                $relations = $this->generateManyManyRelations($table, $junctionFks, $relations);
            }
        }

        if ($this->generateRelations === self::RELATIONS_ALL_INVERSE) {
            return $this->addInverseRelations($relations);
        }

        return $relations;
    }

    /**
     * Adds inverse relations
     *
     * @param array $relations relation declarations
     * @return array relation declarations extended with inverse relation names
     * @since 2.0.5
     */
    protected function addInverseRelations($relations)
    {
        $db = $this->getDbConnection();
        $relationNames = [];

        $schemaNames = $this->getSchemaNames();
        foreach ($schemaNames as $schemaName) {
            foreach ($db->schema->getTableSchemas($schemaName) as $table) {
                $className = $this->generateClassName($table->fullName);
                foreach ($table->foreignKeys as $refs) {
                    $refTable = $refs[0];
                    $refTableSchema = $db->getTableSchema($refTable);
                    if ($refTableSchema === null) {
                        // Foreign key could point to non-existing table: https://github.com/yiisoft/yii2-gii/issues/34
                        continue;
                    }
                    unset($refs[0]);
                    $fks = array_keys($refs);

                    $leftRelationName = $this->generateRelationName($relationNames, $table, $fks[0], false);
                    $relationNames[$table->fullName][$leftRelationName] = true;
                    $hasMany = $this->isHasManyRelation($table, $fks);
                    $rightRelationName = $this->generateRelationName(
                        $relationNames,
                        $refTableSchema,
                        $className,
                        $hasMany
                    );
                    $relationNames[$refTableSchema->fullName][$rightRelationName] = true;

                    $relations[$table->fullName][$leftRelationName][0] =
                        rtrim($relations[$table->fullName][$leftRelationName][0], ';')
                        . "->inverseOf('".lcfirst($rightRelationName)."');";
                    $relations[$refTableSchema->fullName][$rightRelationName][0] =
                        rtrim($relations[$refTableSchema->fullName][$rightRelationName][0], ';')
                        . "->inverseOf('".lcfirst($leftRelationName)."');";
                }
            }
        }
        return $relations;
    }

    /**
     * Determines if relation is of has many type
     *
     * @param TableSchema $table
     * @param array $fks
     * @return bool
     * @since 2.0.5
     */
    protected function isHasManyRelation($table, $fks)
    {
        $uniqueKeys = [$table->primaryKey];
        try {
            $uniqueKeys = array_merge($uniqueKeys, $this->getDbConnection()->getSchema()->findUniqueIndexes($table));
        } catch (NotSupportedException $e) {
            // ignore
        }
        foreach ($uniqueKeys as $uniqueKey) {
            if (count(array_diff(array_merge($uniqueKey, $fks), array_intersect($uniqueKey, $fks))) === 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generates the link parameter to be used in generating the relation declaration.
     * @param array $refs reference constraint
     * @return string the generated link parameter.
     */
    protected function generateRelationLink($refs)
    {
        $pairs = [];
        foreach ($refs as $a => $b) {
            $pairs[] = "'$a' => '$b'";
        }

        return '[' . implode(', ', $pairs) . ']';
    }

    /**
     * Checks if the given table is a junction table, that is it has at least one pair of unique foreign keys.
     * @param \yii\db\TableSchema the table being checked
     * @return array|bool all unique foreign key pairs if the table is a junction table,
     * or false if the table is not a junction table.
     */
    protected function checkJunctionTable($table)
    {
        if (count($table->foreignKeys) < 2) {
            return false;
        }
        $uniqueKeys = [$table->primaryKey];
        try {
            $uniqueKeys = array_merge($uniqueKeys, $this->getDbConnection()->getSchema()->findUniqueIndexes($table));
        } catch (NotSupportedException $e) {
            // ignore
        }
        $result = [];
        // find all foreign key pairs that have all columns in an unique constraint
        $foreignKeys = array_values($table->foreignKeys);
        $foreignKeysCount = count($foreignKeys);

        for ($i = 0; $i < $foreignKeysCount; $i++) {
            $firstColumns = $foreignKeys[$i];
            unset($firstColumns[0]);

            for ($j = $i + 1; $j < $foreignKeysCount; $j++) {
                $secondColumns = $foreignKeys[$j];
                unset($secondColumns[0]);

                $fks = array_merge(array_keys($firstColumns), array_keys($secondColumns));
                foreach ($uniqueKeys as $uniqueKey) {
                    if (count(array_diff(array_merge($uniqueKey, $fks), array_intersect($uniqueKey, $fks))) === 0) {
                        // save the foreign key pair
                        $result[] = [$foreignKeys[$i], $foreignKeys[$j]];
                        break;
                    }
                }
            }
        }
        return empty($result) ? false : $result;
    }

    /**
     * Generate a relation name for the specified table and a base name.
     * @param array $relations the relations being generated currently.
     * @param \yii\db\TableSchema $table the table schema
     * @param string $key a base name that the relation name may be generated from
     * @param bool $multiple whether this is a has-many relation
     * @return string the relation name
     */
    protected function generateRelationName($relations, $table, $key, $multiple)
    {
        static $baseModel;
        /* @var $baseModel \yii\db\ActiveRecord */
        if ($baseModel === null) {
            $baseClass = $this->baseClass;
            $baseClassReflector = new \ReflectionClass($baseClass);
            if ($baseClassReflector->isAbstract()) {
                $baseClassWrapper =
                    'namespace ' . __NAMESPACE__ . ';'.
                    'class GiiBaseClassWrapper extends \\' . $baseClass . ' {' .
                        'public static function tableName(){' .
                            'return "' . addslashes($table->fullName) . '";' .
                        '}' .
                    '};' .
                    'return new GiiBaseClassWrapper();';
                $baseModel = eval($baseClassWrapper);
            } else {
                $baseModel = new $baseClass();
            }
            $baseModel->setAttributes([]);
        }

        if (!empty($key) && strcasecmp($key, 'id')) {
            if (substr_compare($key, 'id', -2, 2, true) === 0) {
                $key = rtrim(substr($key, 0, -2), '_');
            } elseif (substr_compare($key, 'id_', 0, 3, true) === 0) {
                $key = ltrim(substr($key, 3, strlen($key)), '_');
            }
        }
        if ($multiple) {
            $key = Inflector::pluralize($key);
        }
        $name = $rawName = Inflector::id2camel($key, '_');
        $i = 0;
        while ($baseModel->hasProperty(lcfirst($name))) {
            $name = $rawName . ($i++);
        }
        while (isset($table->columns[lcfirst($name)])) {
            $name = $rawName . ($i++);
        }
        while (isset($relations[$table->fullName][$name])) {
            $name = $rawName . ($i++);
        }

        return $name;
    }

    /**
     * Validates the [[db]] attribute.
     */
    public function validateDb()
    {
        if (!Yii::$app->has($this->db)) {
            $this->addError('db', 'There is no application component named "db".');
        } elseif (!Yii::$app->get($this->db) instanceof Connection) {
            $this->addError('db', 'The "db" application component must be a DB connection instance.');
        }
    }

    /**
     * Validates the namespace.
     *
     * @param string $attribute Namespace variable.
     */
    public function validateNamespace($attribute)
    {
        $value = $this->$attribute;
        $value = ltrim($value, '\\');
        $path = Yii::getAlias('@' . str_replace('\\', '/', $value), false);
        if ($path === false) {
            $this->addError($attribute, 'Namespace must be associated with an existing directory.');
        }
    }

    /**
     * Validates the [[modelClass]] attribute.
     */
    public function validateModelClass()
    {
        if ($this->isReservedKeyword($this->modelClass)) {
            $this->addError('modelClass', 'Class name cannot be a reserved PHP keyword.');
        }
        if ((empty($this->tableName) || substr_compare($this->tableName, '*', -1, 1)) && $this->modelClass == '') {
            $this->addError('modelClass', 'Model Class cannot be blank if table name does not end with asterisk.');
        }
    }

    /**
     * Validates the [[tableName]] attribute.
     */
    public function validateTableName()
    {
        if (strpos($this->tableName, '*') !== false && substr_compare($this->tableName, '*', -1, 1)) {
            $this->addError('tableName', 'Asterisk is only allowed as the last character.');

            return;
        }
        $tables = $this->getTableNames();
        if (empty($tables)) {
            $this->addError('tableName', "Table '{$this->tableName}' does not exist.");
        } else {
            foreach ($tables as $table) {
                $class = $this->generateClassName($table);
                if ($this->isReservedKeyword($class)) {
                    $this->addError('tableName', "Table '$table' will generate a class which is a reserved PHP keyword.");
                    break;
                }
            }
        }
    }

    protected $tableNames;
    protected $classNames;

    /**
     * @return array the table names that match the pattern specified by [[tableName]].
     */
    protected function getTableNames()
    {
        if ($this->tableNames !== null) {
            return $this->tableNames;
        }
        $db = $this->getDbConnection();
        if ($db === null) {
            return [];
        }
        $tableNames = [];
        if (strpos($this->tableName, '*') !== false) {
            if (($pos = strrpos($this->tableName, '.')) !== false) {
                $schema = substr($this->tableName, 0, $pos);
                $pattern = '/^' . str_replace('*', '\w+', substr($this->tableName, $pos + 1)) . '$/';
            } else {
                $schema = '';
                $pattern = '/^' . str_replace('*', '\w+', $this->tableName) . '$/';
            }

            foreach ($db->schema->getTableNames($schema) as $table) {
                if (preg_match($pattern, $table)) {
                    $tableNames[] = $schema === '' ? $table : ($schema . '.' . $table);
                }
            }
        } elseif (($table = $db->getTableSchema($this->tableName, true)) !== null) {
            $tableNames[] = $this->tableName;
            $this->classNames[$this->tableName] = $this->modelClass;
        }

        return $this->tableNames = $tableNames;
    }

    /**
     * Generates the table name by considering table prefix.
     * If [[useTablePrefix]] is false, the table name will be returned without change.
     * @param string $tableName the table name (which may contain schema prefix)
     * @return string the generated table name
     */
    public function generateTableName($tableName)
    {
        if (!$this->useTablePrefix) {
            return $tableName;
        }

        $db = $this->getDbConnection();
        if (preg_match("/^{$db->tablePrefix}(.*?)$/", $tableName, $matches)) {
            $tableName = '{{%' . $matches[1] . '}}';
        } elseif (preg_match("/^(.*?){$db->tablePrefix}$/", $tableName, $matches)) {
            $tableName = '{{' . $matches[1] . '%}}';
        }
        return $tableName;
    }

    /**
     * Generates a class name from the specified table name.
     * @param string $tableName the table name (which may contain schema prefix)
     * @param bool $useSchemaName should schema name be included in the class name, if present
     * @return string the generated class name
     */
    protected function generateClassName($tableName, $useSchemaName = null)
    {
        if (isset($this->classNames[$tableName])) {
            return $this->classNames[$tableName];
        }

        $schemaName = '';
        $fullTableName = $tableName;
        if (($pos = strrpos($tableName, '.')) !== false) {
            if (($useSchemaName === null && $this->useSchemaName) || $useSchemaName) {
                $schemaName = substr($tableName, 0, $pos) . '_';
            }
            $tableName = substr($tableName, $pos + 1);
        }

        $db = $this->getDbConnection();
        $patterns = [];
        $patterns[] = "/^{$db->tablePrefix}(.*?)$/";
        $patterns[] = "/^(.*?){$db->tablePrefix}$/";
        if (strpos($this->tableName, '*') !== false) {
            $pattern = $this->tableName;
            if (($pos = strrpos($pattern, '.')) !== false) {
                $pattern = substr($pattern, $pos + 1);
            }
            $patterns[] = '/^' . str_replace('*', '(\w+)', $pattern) . '$/';
        }
        $className = $tableName;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $className = $matches[1];
                break;
            }
        }

        if ($this->standardizeCapitals) {
            $schemaName = ctype_upper(preg_replace('/[_-]/', '', $schemaName)) ? strtolower($schemaName) : $schemaName;
            $className = ctype_upper(preg_replace('/[_-]/', '', $className)) ? strtolower($className) : $className;
            $this->classNames[$fullTableName] = Inflector::camelize(Inflector::camel2words($schemaName.$className));
        } else {
            $this->classNames[$fullTableName] = Inflector::id2camel($schemaName.$className, '_');
        }

        if ($this->singularize) {
            $this->classNames[$fullTableName] = Inflector::singularize($this->classNames[$fullTableName]);
        }

        return $this->classNames[$fullTableName];
    }

    /**
     * Generates a query class name from the specified model class name.
     * @param string $modelClassName model class name
     * @return string generated class name
     */
    protected function generateQueryClassName($modelClassName)
    {
        $queryClassName = $this->queryClass;
        if (empty($queryClassName) || strpos($this->tableName, '*') !== false) {
            $queryClassName = $modelClassName . 'Query';
        }
        return $queryClassName;
    }

    /**
     * @return Connection the DB connection as specified by [[db]].
     */
    protected function getDbConnection()
    {
        return Yii::$app->get($this->db, false);
    }

    /**
     * @return string|null driver name of db connection.
     * In case db is not instance of \yii\db\Connection null will be returned.
     * @since 2.0.6
     */
    protected function getDbDriverName()
    {
        /** @var Connection $db */
        $db = $this->getDbConnection();
        return $db instanceof \yii\db\Connection ? $db->driverName : null;
    }

    /**
     * Checks if any of the specified columns is auto incremental.
     * @param \yii\db\TableSchema $table the table schema
     * @param array $columns columns to check for autoIncrement property
     * @return bool whether any of the specified columns is auto incremental.
     */
    protected function isColumnAutoIncremental($table, $columns)
    {
        foreach ($columns as $column) {
            if (isset($table->columns[$column]) && $table->columns[$column]->autoIncrement) {
                return true;
            }
        }

        return false;
    }
}
