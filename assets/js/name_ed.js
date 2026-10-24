// Тағам атауының шағын редакторы: таңдалған сөзді «қалың қызыл» ету (<b class="cl_red">), «тазалау».
// Белгілеу: <div class="nm_ed"> ішінде .nm_ce (contenteditable) және жасырын input (сақталатын HTML).
(function () {
	var CLS = ['cl_red', 'cl_green', 'cl_blue'], TAGS = ['B', 'I', 'SPAN'];
	var esc = function (s) { return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); };

	// тек рұқсат етілген тегтер қалады, қалғаны мәтінге айналады
	function clean(node) {
		var out = '';
		node.childNodes.forEach(function (n) {
			if (n.nodeType === 3) out += esc(n.nodeValue.replace(/\s+/g, ' '));
			else if (n.nodeType === 1) {
				var inner = clean(n);
				if (TAGS.indexOf(n.tagName) > -1 && inner.trim() !== '') {
					var t = n.tagName.toLowerCase(), c = CLS.filter(function (x) { return n.classList.contains(x); })[0];
					out += '<' + t + (c ? ' class="' + c + '"' : '') + '>' + inner + '</' + t + '>';
				} else out += inner;
			}
		});
		return out;
	}
	function sync(ed) { ed.querySelector('input[type=hidden]').value = clean(ed.querySelector('.nm_ce')).trim(); }

	function init(ed) {
		if (ed.dataset.ok) return; ed.dataset.ok = 1;
		var ce = ed.querySelector('.nm_ce');
		ce.addEventListener('input', function () { sync(ed); });
		ce.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
		ce.addEventListener('paste', function (e) {
			e.preventDefault();
			var t = (e.clipboardData || window.clipboardData).getData('text').replace(/\s+/g, ' ');
			document.execCommand('insertText', false, t);
		});
		ed.querySelector('.nm_red').addEventListener('mousedown', function (e) { e.preventDefault(); }); // таңдау жоғалмасын
		ed.querySelector('.nm_red').addEventListener('click', function () {
			var sel = window.getSelection();
			if (!sel.rangeCount) return;
			var r = sel.getRangeAt(0);
			if (r.collapsed || !ce.contains(r.commonAncestorContainer)) { ce.focus(); return; }
			var b = document.createElement('b'); b.className = 'cl_red';
			b.appendChild(r.extractContents());
			r.insertNode(b);
			ce.innerHTML = clean(ce); // ішкі қайталанған тегтерді тазалау
			sync(ed);
		});
		ed.querySelector('.nm_clr').addEventListener('click', function () { ce.textContent = ce.textContent; sync(ed); ce.focus(); });
		sync(ed);
	}
	function set(ed, html) {
		ed.querySelector('.nm_ce').innerHTML = clean(parse(html));
		sync(ed);
	}
	// DOMParser скрипт пен onerror-ды іске қоспайды
	function parse(html) { return new DOMParser().parseFromString(html || '', 'text/html').body; }
	function text(html) { return parse(html).textContent.trim(); }

	window.nameEd = { init: init, set: set, text: text, clean: function (html) { return clean(parse(html)); } };
	document.querySelectorAll('.nm_ed').forEach(init);
})();
