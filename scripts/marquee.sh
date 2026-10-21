#!/bin/bash
# Start / stop the Marquee names script.   marquee.sh start|stop|restart|status
# Runs it as the fpp user, in the background, with its log in FPP's logs folder.
: "${FPPDIR:=/opt/fpp}"
. "${FPPDIR}/scripts/common" 2>/dev/null
: "${MEDIADIR:=/home/fpp/media}"
: "${LOGDIR:=${MEDIADIR}/logs}"

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DATA="${MEDIADIR}/plugindata/fpp-marquee"
CONF="${DATA}/marquee_names.json"
LOG="${LOGDIR}/plugin-fpp-marquee.log"
SCRIPT="${PLUGIN_DIR}/marquee_names.py"

note() {   # one line in the plugin log (kept writable for the fpp user the script runs as)
	echo "$(date '+%Y-%m-%d %H:%M:%S') $*" >> "$LOG"
	[ "$(id -u)" = "0" ] && chown "${FPPUSER:-fpp}:${FPPGROUP:-fpp}" "$LOG" 2>/dev/null
	return 0
}

pid_running() {   # prints the pid of this plugin's names script, if it's running
	pgrep -f -- "^([^ ]*/)?python3?(\.[0-9]+)? ${SCRIPT}( |\$)" | head -1 | grep .
}

old_running() {   # a names script someone put on the FPP by hand (Command Presets -> Run Script)
	pgrep -f -- "^([^ ]*/)?python3?(\.[0-9]+)? [^ ]*marquee_names\.py" | while read -r p; do
		grep -q -- "${SCRIPT}" "/proc/$p/cmdline" 2>/dev/null || echo "$p"
	done | head -1 | grep .
}

start() {
	if pid_running >/dev/null; then
		return 0
	fi
	[ -f "$CONF" ] || { note "not connected yet: paste your connection code in Content Setup -> Marquee"; return 0; }
	if old_running >/dev/null; then
		note "not starting: the names script you put on the FPP by hand is still running - remove its Run Script command preset (see Content Setup -> Marquee)"
		return 0
	fi
	local run="env MARQUEE_PLUGIN=1 MARQUEE_NAMES_CONFIG=$CONF MARQUEE_DATA=$DATA MARQUEE_LOCK=$DATA/marquee_names.pid MARQUEE_LOG=$LOG python3 $SCRIPT"
	[ "$(id -u)" = "0" ] && [ -f "$LOG" ] && chown "${FPPUSER:-fpp}:${FPPGROUP:-fpp}" "$LOG" 2>/dev/null
	if [ "$(id -u)" = "0" ]; then
		setsid nohup runuser -u "${FPPUSER:-fpp}" -- $run </dev/null >/dev/null 2>&1 &
	else
		setsid nohup $run </dev/null >/dev/null 2>&1 &
	fi
}

stop() {
	local p
	p=$(pid_running) || return 0
	kill "$p" 2>/dev/null
	for _ in 1 2 3 4 5 6 7 8 9 10; do
		[ -d "/proc/$p" ] || break
		sleep 0.3
	done
	[ -d "/proc/$p" ] && kill -9 "$p" 2>/dev/null
	return 0
}

case "$1" in
	start) start ;;
	stop) stop ;;
	restart) stop; start ;;
	status) if p=$(pid_running); then echo "running $p"; elif p=$(old_running); then echo "byhand $p"; else echo "stopped"; fi ;;
	*) echo "usage: $0 start|stop|restart|status"; exit 1 ;;
esac
exit 0
