#!/bin/zsh
set -euo pipefail

PROJECT_ROOT="/Users/m1/Sites/cms"
PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
if [ -z "$PHP_BIN" ]; then
  PHP_BIN="/opt/homebrew/opt/php@8.3/bin/php"
fi
ARTISAN="$PROJECT_ROOT/artisan"
LOG_FILE="$PROJECT_ROOT/storage/logs/queue-cron.log"

# Tune this as needed (automatic burst processing within each cron minute)
MAX_JOBS_PER_TICK="${MAX_JOBS_PER_TICK:-10}"
QUEUES="${QUEUES:-high,default}"
TRIES="${TRIES:-3}"
TIMEOUT="${TIMEOUT:-600}"
RUN_WINDOW_SECONDS="${RUN_WINDOW_SECONDS:-55}"
TICK_SECONDS="${TICK_SECONDS:-2}"

mkdir -p "$(dirname "$LOG_FILE")"
cd "$PROJECT_ROOT"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] queue cron tick started (php=$PHP_BIN queues=$QUEUES max_jobs_per_tick=$MAX_JOBS_PER_TICK window=${RUN_WINDOW_SECONDS}s interval=${TICK_SECONDS}s)." >> "$LOG_FILE"

set +e
start_ts="$(date +%s)"
last_exit=0

"$PHP_BIN" "$ARTISAN" schedule:run >> "$LOG_FILE" 2>&1
schedule_exit=$?
if [ "$schedule_exit" -ne 0 ]; then
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] schedule:run finished with non-zero exit ($schedule_exit)." >> "$LOG_FILE"
fi

while true; do
  now_ts="$(date +%s)"
  elapsed=$((now_ts - start_ts))
  if [ "$elapsed" -ge "$RUN_WINDOW_SECONDS" ]; then
    break
  fi

  "$PHP_BIN" "$ARTISAN" queue:work \
    --stop-when-empty \
    --max-jobs="$MAX_JOBS_PER_TICK" \
    --sleep=1 \
    --tries="$TRIES" \
    --timeout="$TIMEOUT" \
    --queue="$QUEUES" >> "$LOG_FILE" 2>&1
  last_exit=$?

  sleep "$TICK_SECONDS"
done

exit_code=$last_exit
set -e
echo "[$(date '+%Y-%m-%d %H:%M:%S')] queue cron tick finished (exit=$exit_code)." >> "$LOG_FILE"
exit "$exit_code"
