// Файлды браузерден тікелей Google Drive-қа жүктеу. Хостингке файл түспейді, базада тек сілтемесі.
// gdUpload(file, kind) → Promise { ok, file: {id, name, url} } немесе { ok: 0, err, msg }
(function () {
	function post(url, data) {
		var fd = new FormData();
		for (var k in data) fd.append(k, data[k]);
		return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
	}
	var ticket = {};
	function getTicket(kind) {
		var t = ticket[kind];
		if (t && t.until > Date.now()) return Promise.resolve(t);
		return post('/files/get.php?ticket', { kind: kind }).then(function (d) {
			if (!d.ok) throw d;
			d.until = Date.now() + 40 * 60 * 1000; // токен 1 сағат жарамды, ертерек жаңартамыз
			ticket[kind] = d;
			return d;
		});
	}
	function stamp() { var d = new Date(), p = function (n) { return (n < 10 ? '0' : '') + n; }; return d.getFullYear() + p(d.getMonth() + 1) + p(d.getDate()) + '_' + p(d.getHours()) + p(d.getMinutes()) + p(d.getSeconds()); }

	window.gdUpload = function (file, kind) {
		return getTicket(kind).then(function (t) {
			var meta = { name: stamp() + '_' + (file.name || 'file'), parents: [t.folder] };
			var fd = new FormData();
			fd.append('metadata', new Blob([JSON.stringify(meta)], { type: 'application/json' }));
			fd.append('file', file);
			return fetch('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id', {
				method: 'POST', headers: { Authorization: 'Bearer ' + t.token }, body: fd
			}).then(function (r) {
				if (r.status == 401) { delete ticket[kind]; }
				return r.json().then(function (j) { if (!r.ok || !j.id) throw { ok: 0, msg: 'Drive-қа жүктелмеді (' + r.status + ')' }; return j; });
			});
		}).then(function (j) {
			return post('/files/get.php?register', { kind: kind, drive_id: j.id });
		}).then(function (d) {
			if (!d.ok) throw d;
			return d;
		}).catch(function (e) {
			return e && e.ok === 0 ? e : { ok: 0, err: 'net', msg: 'Байланыс қатесі' + (e && e.message ? ': ' + e.message : '') };
		});
	};
	window.gdLink = function (ids, kind, refId) {
		if (!ids || !ids.length || !refId) return;
		var fd = new FormData(); fd.append('kind', kind); fd.append('ids', ids.join(',')); fd.append('ref_id', refId);
		// бет ауысып кетсе де жетсін
		if (navigator.sendBeacon) navigator.sendBeacon('/files/get.php?link', fd);
		else fetch('/files/get.php?link', { method: 'POST', body: fd, credentials: 'same-origin', keepalive: true });
	};
})();
