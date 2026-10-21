# Marquee – Your Name in Lights (FPP plugin)

Puts the first names kids send on your [Marquee](https://github.com/rremesar1-collab/marquee) show website up on your matrix, TV or projector.

This plugin runs the Marquee names script for you, so there are no files to upload and no Command Presets to set up.

## Set it up
1. On the FPP that plays your show: **Content Setup → Plugin Manager**, find **Marquee** and click **Install**.
2. On your website: **Marquee → Settings → Names & FPP → Connect your FPP**, copy the connection code.
3. On the FPP: **Content Setup → Marquee**, paste the code and click **Connect**.

Within a minute your website shows the setup check. Your screens, the names break and everything else are set on your website; the FPP picks up changes by itself.

## Already put the script on your FPP by hand?
Remove the old one so they don't both run: delete the FPPD_STARTED Command Preset that runs `marquee_names.py`, delete `marquee_names.py` from File Manager → Scripts, and restart FPPD. The plugin page reminds you if it finds it.

## What it sends, and where
Only to your own website (the address in your connection code): what's playing, which names were shown and the setup check. While you have a Marquee Support session open on your website, it also sends this FPP's status, playlists, sequences and log, and Marquee Support can ask it to run the setup check, set up the names break playlists again, restart the names script or show a test name. Each one shows in your support chat.

## Files
- Settings (website address and private key): `plugindata/fpp-marquee/`
- Log: `logs/plugin-fpp-marquee.log`
- Spoken names: `music/marquee-voice-*` (newest 300 kept)

Uninstalling stops the script and removes the saved spoken names. Your connection settings stay, so reinstalling or updating picks up where it left off; click Disconnect on the plugin page first if you want this FPP to forget your website. The names break playlists and their audio stay, since your show may use them.

## Help
Open an issue, or use **Marquee → Get help** on your website.
