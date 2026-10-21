#!/bin/bash
# Marquee plugin uninstall (safe to run again): stop the names script, remove its settings and saved spoken names.
# The names break playlists and their audio stay, since your show may use them.
: "${FPPDIR:=/opt/fpp}"
. "${FPPDIR}/scripts/common"
"$(dirname "$0")/marquee.sh" stop
[ -d "${MEDIADIR}/plugindata/fpp-marquee" ] && rm -rf "${MEDIADIR}/plugindata/fpp-marquee"
rm -f "${MEDIADIR}"/music/marquee-voice-* 2>/dev/null
exit 0
