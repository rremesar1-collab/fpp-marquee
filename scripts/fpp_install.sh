#!/bin/bash
# Marquee plugin install (safe to run again).
: "${FPPDIR:=/opt/fpp}"
. "${FPPDIR}/scripts/common"
DATA="${MEDIADIR}/plugindata/fpp-marquee"
mkdir -p "$DATA"
chown "${FPPUSER:-fpp}:${FPPGROUP:-fpp}" "$DATA"
chmod 700 "$DATA"
chmod +x "$(dirname "$0")"/*.sh
# Already connected (an update or a reinstall)? Start it again with the new script.
"$(dirname "$0")/marquee.sh" restart
exit 0
