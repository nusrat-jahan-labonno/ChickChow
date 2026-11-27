// ---------- Profile dropdown ----------
var userBtn = document.getElementById('userBtn'), userDd = document.getElementById('userDd');
function closeUserDd() { if (userDd && userDd.classList.contains('open')) { userDd.classList.remove('open'); userBtn.setAttribute('aria-expanded', 'false'); } }
if (userBtn && userDd) {
    userBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = userDd.classList.toggle('open');
        userBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    userDd.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', closeUserDd);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeUserDd(); });
}

// Show / hide password (eye button)
document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('.pw-eye') : null;
    if (!b) return;
    var inp = b.parentNode.querySelector('input');
    if (!inp) return;
    var show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    b.classList.toggle('on', show);
    b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});

// Password strength bar
document.querySelectorAll('input[data-strength]').forEach(function (inp) {
    var bar = document.getElementById(inp.getAttribute('data-strength'));
    if (!bar) return;
    inp.addEventListener('input', function () {
        var v = inp.value, s = 0;
        if (v.length >= 6) s++;
        if (v.length >= 10) s++;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
        if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
        if (!v) s = 0;
        bar.setAttribute('data-level', s);
        var t = bar.querySelector('small');
        if (t) t.textContent = v ? ['Too short', 'Weak', 'Okay', 'Good', 'Strong'][s] : '';
    });
});

// Mobile menu (website + admin)
var navToggle = document.getElementById('navToggle');
var mainNav = document.getElementById('mainNav');
if (navToggle && mainNav) {
    navToggle.addEventListener('click', function () { mainNav.classList.toggle('open'); });
}
var adminToggle = document.getElementById('adminToggle');
var adminSide = document.getElementById('adminSide');
if (adminToggle && adminSide) {
    adminToggle.addEventListener('click', function () { adminSide.classList.toggle('open'); });
}

// Hide alert messages after 5 seconds
document.querySelectorAll('.alert').forEach(function (el) {
    setTimeout(function () {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 500);
    }, 5000);
});

// Client-side form validation with visible messages (the server validates too)
function fieldMsg(f) {
    if (!f.value.trim()) return f.tagName === 'SELECT' ? 'Please select an option.' : 'This field is required.';
    if (f.minLength > 0 && f.value.length < f.minLength) return 'Enter at least ' + f.minLength + ' characters.';
    if (f.type === 'email' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.value)) return 'Enter a valid email address.';
    return '';
}
function setFieldError(f, msg) {
    f.classList.toggle('invalid', !!msg);
    var holder = f.closest('.form-group') || f.parentNode;
    var old = holder.querySelector('.field-error.js'); if (old) old.remove();
    if (msg) { var d = document.createElement('div'); d.className = 'field-error js'; d.textContent = msg; holder.appendChild(d); }
}
document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        var ok = true;
        form.querySelectorAll('input[required], textarea[required], select[required]').forEach(function (f) {
            if (f.disabled) return;
            var msg = fieldMsg(f); setFieldError(f, msg); if (msg) ok = false;
        });
        var p = form.querySelector('input[name=password]'), c = form.querySelector('input[name=confirm]');
        if (p && c && p.value !== c.value) { setFieldError(c, 'Passwords do not match.'); ok = false; }
        if (!ok) { e.preventDefault(); var first = form.querySelector('.invalid'); if (first) first.focus(); }
    });
    form.querySelectorAll('input, textarea, select').forEach(function (f) {
        f.addEventListener('input', function () { if (f.classList.contains('invalid') && !fieldMsg(f)) setFieldError(f, ''); });
    });
});

// ---------- Mascot pop-up when something is added to the cart (no page reload) ----------
var popEl = null, popTimer = null;
function showToast(msg, isError) {
    var base = document.body.getAttribute('data-base') || '';
    if (!popEl) {
        popEl = document.createElement('div'); popEl.className = 'cart-pop';
        popEl.innerHTML = '<img alt=""><div class="pop-msg"></div>';
        document.body.appendChild(popEl);
    }
    popEl.querySelector('img').src = base + '/assets/images/' + (isError ? 'mascot-sad.png' : 'cart-mascot.png');
    popEl.querySelector('.pop-msg').textContent = msg;
    popEl.classList.toggle('error', !!isError);
    popEl.classList.remove('show'); void popEl.offsetWidth; popEl.classList.add('show');
    clearTimeout(popTimer);
    popTimer = setTimeout(function () { popEl.classList.remove('show'); }, 2300);
}
function setCartCount(n) {
    var link = document.getElementById('cartLink'); if (!link) return;
    var b = link.querySelector('.badge');
    if (!b) { b = document.createElement('span'); b.className = 'badge'; link.appendChild(b); }
    b.textContent = n; b.classList.remove('bump'); void b.offsetWidth; b.classList.add('bump');
}
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.matches('form[data-ajax-cart]')) return;
    e.preventDefault();
    var btn = form.querySelector('button');
    fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            showToast(d.message, !d.ok);
            if (typeof d.count !== 'undefined') setCartCount(d.count);
            if (d.ok && btn) { btn.classList.add('added'); setTimeout(function () { btn.classList.remove('added'); }, 600); }
        })
        .catch(function () { HTMLFormElement.prototype.submit.call(form); });
});

// ---------- Cart drawer (pop-out panel) ----------
var drawer = document.getElementById('cartDrawer'), overlay = document.getElementById('drawerOverlay');
var drawerBody = document.getElementById('drawerBody'), drawerCount = document.getElementById('drawerCount');
function baseUrl() { return document.body.getAttribute('data-base') || ''; }
function setPanel(html, count) {
    drawerBody.innerHTML = html;
    if (typeof count !== 'undefined') { drawerCount.textContent = count > 0 ? '(' + count + ' item' + (count === 1 ? '' : 's') + ')' : ''; setCartCount(count); }
}
function openDrawer() {
    if (!drawer) return;
    closeUserDd();
    drawerBody.innerHTML = '<div class="drawer-loading">Loading your cart...</div>';
    overlay.hidden = false; void overlay.offsetWidth; overlay.classList.add('show');
    drawer.classList.add('open'); drawer.setAttribute('aria-hidden', 'false'); document.body.classList.add('drawer-lock');
    fetch(baseUrl() + '/cart.php?panel=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); }).then(function (d) { setPanel(d.panel, d.count); })
        .catch(function () { window.location.href = baseUrl() + '/cart.php'; });
}
function closeDrawer() {
    if (!drawer) return;
    drawer.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true'); overlay.classList.remove('show'); document.body.classList.remove('drawer-lock');
    setTimeout(function () { if (!drawer.classList.contains('open')) overlay.hidden = true; }, 300);
}
if (drawer) {
    var cl = document.getElementById('cartLink');
    if (cl) cl.addEventListener('click', function (e) { if (e.ctrlKey || e.metaKey || e.shiftKey) return; e.preventDefault(); openDrawer(); });
    document.getElementById('drawerClose').addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
    // + / - / remove / coupon forms inside the drawer update the panel without a reload
    drawerBody.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('form[data-panel-form]')) return;
        e.preventDefault();
        var act = (form.querySelector('input[name=action]') || {}).value;
        fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.panel) setPanel(d.panel, d.count);
                if (act && act.indexOf('coupon') === 0 && d.message && !d.ok) {
                    var box = drawerBody.querySelector('.coupon-box');
                    if (box) { var m = document.createElement('div'); m.className = 'coupon-flash ' + (d.ok ? 'ok' : 'err'); m.textContent = d.message; box.appendChild(m); }
                }
            })
            .catch(function () { window.location.href = baseUrl() + '/cart.php'; });
    });
}
// coupon hint chips (drawer + cart page): click to fill the code
document.addEventListener('click', function (e) {
    var chip = e.target.closest ? e.target.closest('.hint-chip') : null;
    if (!chip) return;
    var box = chip.closest('.coupon-box'), inp = box && box.querySelector('input[name=code]');
    if (inp) { inp.value = chip.getAttribute('data-code'); inp.focus(); }
});

// ---------- Copy coupon code (home page) ----------
document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
        var code = b.getAttribute('data-copy'), tag = b.querySelector('span');
        var done = function () { b.classList.add('copied'); if (tag) tag.textContent = 'Copied!'; setTimeout(function () { b.classList.remove('copied'); if (tag) tag.textContent = 'Copy'; }, 1600); };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(code).then(done, done);
        else { var t = document.createElement('textarea'); t.value = code; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (x) {} t.remove(); done(); }
    });
});

// ---------- Review form: a star rating is required ----------
var rf = document.getElementById('reviewForm');
if (rf) rf.addEventListener('submit', function (e) {
    if (!rf.querySelector('input[name=rating]:checked')) { e.preventDefault(); var er = document.getElementById('ratingErr'); if (er) er.hidden = false; }
});

// ---------- Cart page: keep scroll position after + / - ----------
document.querySelectorAll('form.keep-scroll').forEach(function (f) {
    f.addEventListener('submit', function () { try { sessionStorage.setItem('ck_scroll', JSON.stringify({ p: location.pathname, y: window.scrollY })); } catch (e) {} });
});
try {
    var sv = JSON.parse(sessionStorage.getItem('ck_scroll') || 'null');
    if (sv && sv.p === location.pathname) { window.scrollTo(0, sv.y); }
    sessionStorage.removeItem('ck_scroll');
} catch (e) {}

// ---------- Checkout: live delivery charge by area ----------
var cf = document.getElementById('checkoutForm');
if (cf) {
    var quotes = JSON.parse(cf.getAttribute('data-quotes') || '{}');
    var sub = parseFloat(cf.getAttribute('data-subtotal')) || 0, cur = cf.getAttribute('data-cur') || '';
    var areaSel = document.getElementById('area'), addrBox = document.getElementById('address'), saved = document.getElementById('saved');
    var fmt = function (n) { return cur + Math.round(n).toLocaleString('en-US'); };
    var updateCharge = function () {
        var q = quotes[areaSel.value], parts = document.getElementById('delParts');
        document.getElementById('delFee').textContent = q ? fmt(q.total) : '\u2014';
        document.getElementById('grandTotal').textContent = fmt(sub + (q ? q.total : 0));
        parts.textContent = '';
        if (!q) { parts.textContent = 'Select your area to see the delivery charge.'; return; }
        Object.keys(q.parts).forEach(function (k) {
            var pt = q.parts[k], d = document.createElement('div');
            d.textContent = pt.name + ' \u00B7 ' + pt.km + ' km \u00B7 ' + fmt(pt.fee); parts.appendChild(d);
        });
    };
    areaSel.addEventListener('change', updateCharge);
    if (saved) saved.addEventListener('change', function () {
        var o = saved.options[saved.selectedIndex];
        if (o && o.getAttribute('data-area')) { areaSel.value = o.getAttribute('data-area'); addrBox.value = o.getAttribute('data-addr'); updateCharge(); }
    });
}
