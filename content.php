<div class="container-fluid px-0" id="mqPlugin">
	<h2>Marquee – Your Name in Lights</h2>
	<p class="text-body-secondary">Kids send their first name on your Marquee website and this FPP puts it up on your screens. Your screens, the names break and everything else are set on your website (Marquee → Settings → Names &amp; FPP); this page only connects this FPP to it.</p>

	<div class="alert alert-warning d-none" id="mqByhand">
		<b>The names script you put on this FPP by hand is still set up.</b>
		The plugin runs it for you now, so remove the old one or both will try to run:
		<ol class="mb-0 mt-1">
			<li>Status/Control → Command Presets: delete the <b>FPPD_STARTED</b> preset that runs <b>marquee_names.py</b>, and Save.</li>
			<li>Content Setup → File Manager → Scripts: delete <b>marquee_names.py</b> (and marquee_names.json if it's there).</li>
			<li>Restart FPPD.</li>
		</ol>
	</div>

	<div class="card mb-3">
		<div class="card-body">
			<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
				<h3 class="h5 mb-0 me-2">Status</h3>
				<span class="badge text-bg-secondary" id="mqState">Checking…</span>
				<span class="badge text-bg-light border" id="mqVer"></span>
				<span class="text-body-secondary small" id="mqSite"></span>
			</div>
			<div class="d-flex flex-wrap gap-2" id="mqConnectedBtns">
				<button type="button" class="buttons btn btn-outline-secondary" id="mqRestart">Restart</button>
				<div class="input-group" style="max-width: 22rem">
					<input type="text" class="form-control" id="mqTestText" maxlength="30" value="MERRY CHRISTMAS!" aria-label="Test text">
					<button type="button" class="buttons btn btn-outline-secondary" id="mqTest">Show on my screens</button>
				</div>
				<button type="button" class="buttons btn btn-outline-danger ms-auto" id="mqDisconnect">Disconnect</button>
			</div>
			<div class="small mt-2" id="mqMsg" role="status"></div>
		</div>
	</div>

	<div class="card mb-3 d-none" id="mqTiming">
		<div class="card-body">
			<h3 class="h5">Your own names break audio</h3>
			<p class="mb-2">Only if your names break plays <b>your own</b> audio with an announcer (made in xLights, not with the builder on your website). Tell the script where the quiet part is, so names are only said there and never over your announcer. Leave both empty if you use the website's break. <b>Easier:</b> set these on your website instead (Names in Lights → Names break → Names break audio → Use my own names break audio); the website's times win when both are set.</p>
			<div class="alert alert-success py-2 mb-2 d-none" id="mqOwnSite">Set on your website: first name at <b id="mqOwnSiteA"></b> seconds, closing starts at <b id="mqOwnSiteB"></b> seconds. These are the times the names break uses. To change them, go to your website: Marquee → Settings → Names in Lights → Names break.</div>
			<div class="row g-2 align-items-end" style="max-width: 34rem" id="mqOwnLocal">
				<div class="col-sm">
					<label class="form-label small mb-1" for="mqOwnStart">First name at (seconds)</label>
					<input type="number" class="form-control" id="mqOwnStart" min="0" max="600" step="0.1" placeholder="e.g. 12">
					<div class="form-text">When the intro talking ends.</div>
				</div>
				<div class="col-sm">
					<label class="form-label small mb-1" for="mqOwnEnd">Closing starts at (seconds)</label>
					<input type="number" class="form-control" id="mqOwnEnd" min="0" max="600" step="0.1" placeholder="e.g. 19">
					<div class="form-text">When the announcer starts talking again.</div>
				</div>
				<div class="col-sm-auto">
					<button type="button" class="buttons btn btn-success" id="mqOwnSave">Save</button>
				</div>
			</div>
			<div class="form-text mt-2">Tip: in xLights, the times are where your announcer's words end and start again (the timing track shows them). About 3.3 seconds fit one name; the rest of the break shows names that were already said, with no voice.</div>
		</div>
	</div>

	<div class="card mb-3">
		<div class="card-body">
			<h3 class="h5" id="mqConnectTitle">Connect to your website</h3>
			<p class="mb-2">On your website: Marquee → Settings → Names &amp; FPP → <b>Connect your FPP</b>, copy the connection code, paste it here and click Connect.</p>
			<div class="input-group">
				<input type="password" class="form-control font-monospace" id="mqCode" placeholder="MQ1-…" autocomplete="off" aria-label="Connection code">
				<button type="button" class="buttons btn btn-success" id="mqConnect">Connect</button>
			</div>
			<div class="form-text">The code holds your website's address and private key. Keep it private.</div>
		</div>
	</div>

	<div class="card mb-3">
		<div class="card-body">
			<div class="d-flex align-items-center mb-2">
				<h3 class="h5 mb-0">Log</h3>
				<button type="button" class="buttons btn btn-sm btn-outline-secondary ms-auto" id="mqRefresh">Refresh</button>
			</div>
			<pre class="border rounded bg-body-tertiary p-2 small mb-0" id="mqLog" style="max-height: 24rem; overflow: auto; white-space: pre-wrap"></pre>
			<div class="form-text">The full log is in Help → FPP Logs (plugin-fpp-marquee.log).</div>
		</div>
	</div>
	<p class="text-body-secondary small" id="mqVersion"></p>
</div>

<script>
(function () {
	var base = 'api/plugin/fpp-marquee/';
	function $(id) { return document.getElementById(id); }
	function say(msg, kind) {
		var m = $('mqMsg');
		m.className = 'small mt-2 ' + (kind === 'bad' ? 'text-danger' : kind === 'warn' ? 'text-warning-emphasis' : 'text-success');
		m.textContent = msg || '';
	}
	function call(method, ep, body) {
		var opt = { method: method, cache: 'no-store' };
		if (body) { opt.headers = { 'Content-Type': 'application/json' }; opt.body = JSON.stringify(body); }
		return fetch(base + ep, opt).then(function (r) {
			return r.text().then(function (t) {
				try { return JSON.parse(t); } catch (e) { throw new Error('HTTP ' + r.status + ': ' + t.slice(0, 200)); }
			});
		});
	}
	function busy(b, on) { b.disabled = on; }
	var timingLoaded = false;
	function load() {
		return call('GET', 'status').then(function (d) {
			var st = $('mqState'), labels = { running: ['Running', 'text-bg-success'], byhand: ['Old script running', 'text-bg-warning'], stopped: [d.site ? 'Stopped' : 'Not connected', d.site ? 'text-bg-danger' : 'text-bg-secondary'] };
			var l = labels[d.state] || labels.stopped;
			st.textContent = l[0]; st.className = 'badge ' + l[1];
			$('mqSite').textContent = d.site ? 'Connected to ' + d.site : '';
			$('mqConnectedBtns').classList.toggle('d-none', !d.site);
			$('mqConnectTitle').textContent = d.site ? 'Connect to a different website' : 'Connect to your website';
			$('mqByhand').classList.toggle('d-none', !(d.state === 'byhand' || d.byhandFile));
			var log = $('mqLog'), atEnd = log.scrollHeight - log.scrollTop - log.clientHeight < 40;
			log.textContent = (d.log || []).join('\n') || 'Nothing yet.';
			if (atEnd) { log.scrollTop = log.scrollHeight; }
			$('mqVersion').textContent = d.version ? 'Marquee names script ' + d.version : '';
			$('mqVer').textContent = d.version ? 'Script ' + d.version : 'Script version unknown';
			$('mqTiming').classList.toggle('d-none', !d.site);
			var site = d.site_own;   // 3.16.2+: times set on the website win, so show those and hide the boxes here
			$('mqOwnSite').classList.toggle('d-none', !site);
			$('mqOwnLocal').classList.toggle('d-none', !!site);
			if (site) {
				$('mqOwnSiteA').textContent = site.own_start;
				$('mqOwnSiteB').textContent = site.own_end;
			}
			if (!timingLoaded) {
				timingLoaded = true;
				$('mqOwnStart').value = d.own_start ? d.own_start : '';
				$('mqOwnEnd').value = d.own_end ? d.own_end : '';
			}
		}).catch(function (e) { $('mqState').textContent = 'Couldn’t read the status'; $('mqState').title = String(e && e.message || e); console.log('[Marquee] status', e); });
	}
	$('mqConnect').addEventListener('click', function () {
		var b = this, code = $('mqCode').value.trim();
		if (!code) { say('Paste your connection code first.', 'bad'); return; }
		busy(b, true); say('Connecting…', 'warn');
		call('POST', 'connect', { code: code }).then(function (d) {
			say(d.message, d.ok ? (d.warn ? 'warn' : 'ok') : 'bad');
			if (d.ok) { $('mqCode').value = ''; }
			setTimeout(load, 2000);
		}).catch(function () { say('Something went wrong. Try again.', 'bad'); }).then(function () { busy(b, false); });
	});
	$('mqRestart').addEventListener('click', function () {
		var b = this; busy(b, true);
		call('POST', 'restart').then(function (d) { say(d.message, d.ok ? 'ok' : 'bad'); setTimeout(load, 2000); }).then(function () { busy(b, false); });
	});
	$('mqTest').addEventListener('click', function () {
		var b = this; busy(b, true); say('Sending…', 'warn');
		call('POST', 'test', { text: $('mqTestText').value }).then(function (d) { say(d.message, d.ok ? 'ok' : 'bad'); load(); })
			.catch(function () { say('Something went wrong. Try again.', 'bad'); }).then(function () { busy(b, false); });
	});
	$('mqDisconnect').addEventListener('click', function () {
		if (!window.confirm('Disconnect this FPP from your website? Names stop going up here.')) { return; }
		call('POST', 'disconnect').then(function (d) { say(d.message, 'ok'); load(); });
	});
	$('mqOwnSave').addEventListener('click', function () {
		var b = this; busy(b, true); say('Saving…', 'warn');
		call('POST', 'timing', { own_start: $('mqOwnStart').value, own_end: $('mqOwnEnd').value })
			.then(function (d) { say(d.message, d.ok ? 'ok' : 'bad'); setTimeout(load, 2000); })
			.catch(function () { say('Something went wrong. Try again.', 'bad'); }).then(function () { busy(b, false); });
	});
	$('mqRefresh').addEventListener('click', load);
	load();
	setInterval(function () { if (!document.hidden) { load(); } }, 10000);
})();
</script>
