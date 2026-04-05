#!/usr/bin/env bash
# -*- coding: utf-8 -*-
#  MySQL 逻辑备份 + 压缩 + 定期清理
#  兼容 Bash 4+ / PHP7.3 环境无关

############################  可配置区域  ##############################
STORE_DIR='/www/wwwroot/yiiframe/common/backup'   # 备份存放目录
RUN_DIR='/www/server/mysql/bin'                  # mysql bin 目录
USERNAME='root'                                  # 连接账号
PASSWORD=''                                      # 连接密码
SERVER='127.0.0.1'                               # 主机
DATABASE='yiiframe'                              # 要备份的库（单库）
RETAIN_DAYS=7                                    # 普通备份保留天数
RETAIN_MONTH_DAYS=92                             # 10/20/30 号备份保留天数
############################  可配置结束  ##############################

set -euo pipefail   # 遇到错误/未定义变量/管道失败立即退出
DATE=$(date +%F-%H%M%S)                          # 2025-10-09-023000
SQL_FILE="${DATE}.sql"
TAR_FILE="${SQL_FILE}.tar.gz"
LOG_FILE="${STORE_DIR}/backup.log"

# 如目录不存在则创建
mkdir -p "$STORE_DIR"

{
  echo "[$(date '+%F %T')] ====== 备份任务开始 ======"

  # -------------- 1. 导出  --------------
  echo "[$(date '+%F %T')] 开始 mysqldump ..."
  "$RUN_DIR/mysqldump" -h"$SERVER" -u"$USERNAME" -p"$PASSWORD" \
    --single-transaction --routines --triggers --events \
    "$DATABASE" > "${STORE_DIR}/${SQL_FILE}"

  # -------------- 2. 压缩  --------------
  echo "[$(date '+%F %T')] 开始压缩 ..."
  cd "$STORE_DIR"
  tar -zcf "$TAR_FILE" "$SQL_FILE"

  # tar 成功后再删除 sql
  if [[ -f "$TAR_FILE" ]]; then
    rm -f "$SQL_FILE"
    echo "[$(date '+%F %T')] 压缩完成，已删除原始 ${SQL_FILE}"
  else
    echo "[$(date '+%F %T')] 压缩失败，保留 ${SQL_FILE}" >&2
    exit 1
  fi

  # -------------- 3. 清理  --------------
  echo "[$(date '+%F %T')] 开始清理过期备份 ..."
  # 删除 RETAIN_DAYS 之前，且文件名不包含 10/20/30 的备份
  find "$STORE_DIR" -type f -name '*.sql.tar.gz' -mtime +${RETAIN_DAYS} \
       ! -name '*-10-*.tar.gz' ! -name '*-20-*.tar.gz' ! -name '*-30-*.tar.gz' \
       -delete

  # 删除 RETAIN_MONTH_DAYS 之前的所有备份（包括 10/20/30）
  find "$STORE_DIR" -type f -name '*.sql.tar.gz' -mtime +${RETAIN_MONTH_DAYS} -delete

  echo "[$(date '+%F %T')] ====== 备份任务结束 ======"
} >> "$LOG_FILE" 2>&1

exit 0