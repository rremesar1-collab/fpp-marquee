<?php
/*
 * Marquee plugin API, all under /api/plugin/fpp-marquee/
 *   GET  status      - is the names script running, which website, the last log lines
 *   POST connect     - {"code": "MQ1-..."}: save the website address and private key, start the script
 *   POST disconnect  - stop the script and forget the website
 *   POST restart     - restart the script
 *   POST test        - {"text": "TEST"}: show a name on your screens now
 */

function getEndpointsfppmarquee()
{
	return array(
		array('method' => 'GET', 'endpoint' => 'status', 'callback' => 'fppMarqueeStatus'),
		array('method' => 'POST', 'endpoint' => 'connect', 'callback' => 'fppMarqueeConnect'),
		array('method' => 'POST', 'endpoint' => 'disconnect', 'callback' => 'fppMarqueeDisconnect'),
		array('method' => 'POST', 'endpoint' => 'restart', 'callback' => 'fppMarqueeRestart'),
		array('method' => 'POST', 'endpoint' => 'test', 'callback' => 'fppMarqueeTest'),
	);
}

function fppMarqueePaths()
{
	global $settings;
	$media = isset($settings['mediaDirectory']) ? $settings['mediaDirectory'] : '/home/fpp/media';
	$logs = isset($settings['logDirectory']) ? $settings['logDirectory'] : $media . '/logs';
	$data = $media . '/plugindata/fpp-marquee';
	return array(
		'plugin' => __DIR__,
		'data' => $data,
		'conf' => $data . '/marquee_names.json',
		'log' => $logs . '/plugin-fpp-marquee.log',
		'byhand' => $media . '/scripts/marquee_names.py',
		'media' => $media,
	);
}

function fppMarqueeRun($args, $timeout = 20)
{
	$p = fppMarqueePaths();
	$cmd = 'timeout ' . (int) $timeout . ' ' . escapeshellarg($p['plugin'] . '/scripts/marquee.sh') . ' ' . $args . ' 2>&1';
	return trim((string) shell_exec($cmd));
}

function fppMarqueeBody()
{
	$b = json_decode(file_get_contents('php://input'), true);
	return is_array($b) ? $b : $_POST;
}

function fppMarqueeConf()
{
	$p = fppMarqueePaths();
	if (!is_file($p['conf'])) {
		return null;
	}
	$c = json_decode((string) @file_get_contents($p['conf']), true);
	return is_array($c) ? $c : null;
}

function fppMarqueeVersion()
{
	$p = fppMarqueePaths();
	$src = (string) @file_get_contents($p['plugin'] . '/marquee_names.py', false, null, 0, 4000);
	return preg_match('/^VERSION = "([^"]+)"/m', $src, $m) ? $m[1] : '';
}

/* GET /api/plugin/fpp-marquee/status */
function fppMarqueeStatus()
{
	$p = fppMarqueePaths();
	$conf = fppMarqueeConf();
	$state = fppMarqueeRun('status', 5);
	$lines = array();
	if (is_file($p['log'])) {
		$f = new SplFileObject($p['log']);
		$f->seek(PHP_INT_MAX);
		$last = $f->key();
		for ($i = max(0, $last - 80); $i <= $last; $i++) {
			$f->seek($i);
			$l = rtrim((string) $f->current());
			if ($l !== '') {
				$lines[] = $l;
			}
		}
	}
	return json(array(
		'state' => preg_match('/^(running|byhand|stopped)/', $state, $m) ? $m[1] : 'stopped',
		'site' => $conf ? (string) $conf['site'] : '',
		'version' => fppMarqueeVersion(),
		'byhandFile' => is_file($p['byhand']),
		'log' => $lines,
	));
}

/* POST /api/plugin/fpp-marquee/connect  {"code": "MQ1-..."} */
function fppMarqueeConnect()
{
	$p = fppMarqueePaths();
	$b = fppMarqueeBody();
	$code = trim(isset($b['code']) ? (string) $b['code'] : '');
	if (strpos($code, 'MQ1-') !== 0) {
		return json(array('ok' => false, 'message' => 'That isn’t a Marquee connection code. Copy it from your website: Marquee → Settings → Names & FPP → Connect your FPP.'));
	}
	$raw = base64_decode(strtr(substr($code, 4), '-_', '+/'), true);
	$d = $raw ? json_decode($raw, true) : null;
	$site = is_array($d) && isset($d['site']) ? trim((string) $d['site']) : '';
	$key = is_array($d) && isset($d['key']) ? trim((string) $d['key']) : '';
	if (!preg_match('#^https?://[^\s/$.?\#].[^\s]*$#i', $site) || !preg_match('/^[A-Za-z0-9]{20,100}$/', $key)) {
		return json(array('ok' => false, 'message' => 'The connection code looks cut off. Copy it again from your website.'));
	}
	// Try it once, so a wrong code shows up here and not only in the log.
	$url = rtrim($site, '/') . '/wp-admin/admin-ajax.php?action=rr_names_pull&key=' . rawurlencode($key);
	$ctx = stream_context_create(array('http' => array('method' => 'POST', 'timeout' => 12, 'ignore_errors' => true,
		'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: FPP-Marquee-plugin\r\n", 'content' => 'ping=1')));
	$res = @file_get_contents($url, false, $ctx);
	$j = $res ? json_decode($res, true) : null;
	$checked = is_array($j) && !empty($j['success']);
	if (!is_dir($p['data'])) {
		@mkdir($p['data'], 0700, true);
	}
	$old = fppMarqueeConf();
	$conf = is_array($old) ? $old : array();
	$conf['site'] = $site;
	$conf['key'] = $key;
	$tmp = $p['conf'] . '.tmp';
	if (@file_put_contents($tmp, json_encode($conf, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false || !@rename($tmp, $p['conf'])) {
		return json(array('ok' => false, 'message' => 'Couldn’t save the settings in ' . $p['data'] . '. Reinstall the plugin.'));
	}
	@chmod($p['conf'], 0600);
	fppMarqueeRun('restart');
	if ($checked) {
		return json(array('ok' => true, 'message' => 'Connected to ' . parse_url($site, PHP_URL_HOST) . '. The setup check shows on your website within a minute.'));
	}
	return json(array('ok' => true, 'warn' => true, 'message' => 'Saved, but your website didn’t answer yet' . ($res === false ? ' (check that this FPP is online)' : '') . '. The script keeps trying; watch the log below.'));
}

/* POST /api/plugin/fpp-marquee/disconnect */
function fppMarqueeDisconnect()
{
	$p = fppMarqueePaths();
	fppMarqueeRun('stop');
	@unlink($p['conf']);
	return json(array('ok' => true, 'message' => 'Disconnected. Names from your website no longer go up on this FPP.'));
}

/* POST /api/plugin/fpp-marquee/restart */
function fppMarqueeRestart()
{
	if (!fppMarqueeConf()) {
		return json(array('ok' => false, 'message' => 'Paste your connection code first.'));
	}
	fppMarqueeRun('restart');
	return json(array('ok' => true, 'message' => 'Restarted.'));
}

/* POST /api/plugin/fpp-marquee/test  {"text": "TEST"} */
function fppMarqueeTest()
{
	$p = fppMarqueePaths();
	if (!fppMarqueeConf()) {
		return json(array('ok' => false, 'message' => 'Paste your connection code first.'));
	}
	$b = fppMarqueeBody();
	$text = preg_replace('/[^A-Za-z0-9 !\'\-]/', '', isset($b['text']) ? (string) $b['text'] : '');
	$text = substr(trim($text) !== '' ? trim($text) : 'MERRY CHRISTMAS!', 0, 30);
	$env = 'MARQUEE_PLUGIN=1 MARQUEE_NAMES_CONFIG=' . escapeshellarg($p['conf']) . ' MARQUEE_DATA=' . escapeshellarg($p['data']) . ' MARQUEE_LOG=' . escapeshellarg($p['log']);
	$out = trim((string) shell_exec('timeout 40 env ' . $env . ' python3 ' . escapeshellarg($p['plugin'] . '/marquee_names.py') . ' --test ' . escapeshellarg($text) . ' 2>&1'));
	$n = preg_match('/shown on (\d+) screen/', $out, $m) ? (int) $m[1] : 0;
	return json(array('ok' => $n > 0, 'message' => $n > 0 ? 'Shown on ' . $n . ' screen' . ($n > 1 ? 's' : '') . '.' : 'No screen showed it. ' . substr($out, -300)));
}
?>
