#!/bin/bash
# Marquee plugin uninstall (safe to run again): stop the names script and remove the saved spoken names.
# The connection settings stay (in plugindata/fpp-marquee), because FPP can run this while
# reinstalling or updating the plugin; use Disconnect on the plugin page to forget your website.
# The names break playlists and their audio stay too, since your show may use them.
: "${FPPDIR:=/opt/fpp}"
. "${FPPDIR}/scripts/common"
"$(dirname "$0")/marquee.sh" stop
rm -f "${MEDIADIR}"/music/marquee-voice-* 2>/dev/null
exit 0
