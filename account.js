/**
 * Petals Paradise Events — Customer Accounts & Loyalty Rewards (front end)
 * Talks to /api/auth.php. Loaded before app.js; app.js calls:
 *   window.renderAccountPage(page)  -> { html, title, desc }
 *   window.ppeAfterRender(route)    -> after every route render
 */
(function () {
    'use strict';

    const RULES = { perDollar: 10, perDollarOff: 100, minRedeem: 500, step: 500, maxPct: 0.5 };
    const API = '/api/auth.php';

    const state = { loaded: false, loggedIn: false, data: null };
    window.ppeSelectedRedeem = 0;

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = (n) => Number(n || 0).toLocaleString('en-US');
    const money = (n) => '$' + Number(n || 0).toFixed(2).replace(/\.00$/, '');
    const currentRoute = () => (window.location.hash || '#').split('?')[0];
    const hashParam = (key) => new URLSearchParams((window.location.hash.split('?')[1] || '')).get(key);

    async function api(action, body) {
        const opts = body
            ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ action, ...body }) }
            : { credentials: 'same-origin' };
        const res = await fetch(body ? API : `${API}?action=${action}`, opts);
        let json = {};
        try { json = await res.json(); } catch (e) { json = { error: 'Unexpected server response.' }; }
        if (!res.ok && !json.error) json.error = 'Something went wrong. Please try again.';
        return json;
    }

    function setSession(json) {
        if (json && (json.logged_in || json.success) && json.customer) {
            state.loggedIn = true;
            state.data = json;
        } else {
            state.loggedIn = false;
            state.data = null;
        }
        state.loaded = true;
        updateNav();
    }

    async function refresh() {
        try { setSession(await api('me')); } catch (e) { state.loaded = true; updateNav(); }
        const r = currentRoute();
        if (['#profile', '#checkout', '#cart', '#signin', '#signup'].includes(r) && window.ppeCart) window.ppeCart.rerender();
    }

    function balance() { return state.data?.points?.balance || 0; }
    function firstName() { return esc((state.data?.customer?.name || '').split(' ')[0] || 'Account'); }

    function updateNav() {
        document.querySelectorAll('[data-account-link]').forEach(a => {
            if (state.loggedIn) {
                a.setAttribute('href', '#profile');
                a.innerHTML = `👤 ${firstName()} <span class="acct-nav-pts">${fmt(balance())} pts</span>`;
            } else {
                a.setAttribute('href', '#signin');
                a.innerHTML = '👤 Sign In';
            }
        });
    }

    // ── Reusable pieces ──────────────────────────────────────────────────
    const rewardsBanner = () => `
        <div class="acct-rewards-banner" role="note">
            <span class="acct-rewards-icon">🌸</span>
            <span><strong>Petals Rewards:</strong> Earn <strong>10 points for every $1</strong> you spend.
            Redeem <strong>500 points for $5 off</strong> your next order!</span>
            <a href="${state.loggedIn ? '#profile' : '#signup'}" class="acct-rewards-cta">${state.loggedIn ? `You have ${fmt(balance())} pts →` : 'Join free →'}</a>
        </div>`;

    const howItWorks = () => `
        <div class="acct-steps">
            <div class="acct-step"><div class="acct-step-num">1</div><h4>Create a free account</h4><p>Sign up with the email you use for orders. Completed orders from the last 12 months are added automatically.</p></div>
            <div class="acct-step"><div class="acct-step-num">2</div><h4>Earn 10 pts per $1</h4><p>Points are added once your order is <strong>Returned</strong> (pickup) or <strong>Completed</strong> (delivery).</p></div>
            <div class="acct-step"><div class="acct-step-num">3</div><h4>Redeem 500 pts = $5</h4><p>Use points at checkout in steps of 500 (up to 50% of your order). Points never expire.</p></div>
        </div>`;

    const faq = [
        ['How do I earn points?', 'You earn 10 points for every $1 of your final order total (after discounts). Points are added to your account when a pickup order is marked Returned or a delivery order is marked Completed.'],
        ['How do I redeem points?', 'Once you have at least 500 points, sign in and choose how many points to use in the checkout summary. Every 500 points = $5 off, and points can cover up to 50% of your order.'],
        ['Do my past orders count?', 'Yes! When you create an account with the same email you used for past orders, completed orders from the last 12 months are credited automatically.'],
        ['Do points expire?', 'No, your points do not expire.'],
        ['What if my order is cancelled?', 'Any points you used on a cancelled order are returned to your account automatically.'],
    ];
    const faqHtml = () => `
        <div class="acct-faq">
            ${faq.map(([q, a]) => `<details class="acct-faq-item"><summary>${esc(q)}</summary><p>${esc(a)}</p></details>`).join('')}
        </div>`;

    const authShell = (title, subtitle, inner, footer) => `
        <div class="container acct-page">
            <div class="acct-auth-wrap">
                <div class="acct-auth-card">
                    <div class="acct-auth-badge">🌸 Petals Rewards</div>
                    <h1 class="acct-title">${title}</h1>
                    <p class="acct-subtitle">${subtitle}</p>
                    <div id="acct-msg" class="acct-msg" role="alert" aria-live="polite"></div>
                    ${inner}
                    <div class="acct-auth-footer">${footer}</div>
                </div>
                <aside class="acct-auth-side">
                    <h3>Why join?</h3>
                    <ul>
                        <li>⭐ 10 points for every $1 spent</li>
                        <li>🎁 500 points = $5 off</li>
                        <li>📦 Track all your orders in one place</li>
                        <li>🕒 Past 12 months of orders count</li>
                    </ul>
                </aside>
            </div>
        </div>`;

    // ── Pages ────────────────────────────────────────────────────────────
    function pageSignin() {
        if (state.loggedIn) return pageProfile();
        return {
            title: 'Sign In | Petals Paradise Events',
            desc: 'Sign in to your Petals Paradise Events account to view your rewards points and orders.',
            html: authShell('Welcome back', 'Sign in to view your points and orders.', `
                <form id="acct-signin-form" class="acct-form" onsubmit="ppeAccountSubmit(event, 'login')" novalidate>
                    <div class="form-group"><label class="form-label" for="si-email">Email</label>
                        <input id="si-email" name="email" type="email" class="form-control" autocomplete="email" required></div>
                    <div class="form-group"><label class="form-label" for="si-password">Password</label>
                        <input id="si-password" name="password" type="password" class="form-control" autocomplete="current-password" required></div>
                    <button id="si-submit" type="submit" class="btn btn-primary acct-btn">Sign In</button>
                    <a href="#forgot" class="acct-link acct-forgot">Forgot password?</a>
                </form>`,
                `New here? <a href="#signup" class="acct-link">Create a free account</a>`)
        };
    }

    function pageSignup() {
        if (state.loggedIn) return pageProfile();
        return {
            title: 'Create Account | Petals Paradise Events',
            desc: 'Join Petals Rewards: earn 10 points for every $1 and get $5 off for every 500 points.',
            html: authShell('Create your account', 'Earn 10 points for every $1 — 500 points = $5 off.', `
                <form id="acct-signup-form" class="acct-form" onsubmit="ppeAccountSubmit(event, 'signup')" novalidate>
                    <div class="form-group"><label class="form-label" for="su-name">Full Name</label>
                        <input id="su-name" name="name" type="text" class="form-control" autocomplete="name" required></div>
                    <div class="form-group"><label class="form-label" for="su-email">Email</label>
                        <input id="su-email" name="email" type="email" class="form-control" autocomplete="email" required>
                        <small class="acct-hint">Use the email from your past orders to get credit for them.</small></div>
                    <div class="form-group"><label class="form-label" for="su-phone">Phone</label>
                        <input id="su-phone" name="phone" type="tel" class="form-control" autocomplete="tel"></div>
                    <div class="form-group"><label class="form-label" for="su-password">Password</label>
                        <input id="su-password" name="password" type="password" class="form-control" autocomplete="new-password" minlength="8" required>
                        <small class="acct-hint">At least 8 characters.</small></div>
                    <button id="su-submit" type="submit" class="btn btn-primary acct-btn">Create Account</button>
                </form>`,
                `Already have an account? <a href="#signin" class="acct-link">Sign in</a>`)
        };
    }

    function pageForgot() {
        return {
            title: 'Forgot Password | Petals Paradise Events',
            html: authShell('Reset your password', 'Enter your email and we will send you a reset link.', `
                <form id="acct-forgot-form" class="acct-form" onsubmit="ppeAccountSubmit(event, 'forgot')" novalidate>
                    <div class="form-group"><label class="form-label" for="fp-email">Email</label>
                        <input id="fp-email" name="email" type="email" class="form-control" autocomplete="email" required></div>
                    <button id="fp-submit" type="submit" class="btn btn-primary acct-btn">Send Reset Link</button>
                </form>`,
                `<a href="#signin" class="acct-link">Back to sign in</a>`)
        };
    }

    function pageReset() {
        return {
            title: 'Choose a New Password | Petals Paradise Events',
            html: authShell('Choose a new password', 'Enter a new password for your account.', `
                <form id="acct-reset-form" class="acct-form" onsubmit="ppeAccountSubmit(event, 'reset')" novalidate>
                    <input type="hidden" name="token" value="${esc(hashParam('token') || '')}">
                    <div class="form-group"><label class="form-label" for="rp-password">New Password</label>
                        <input id="rp-password" name="password" type="password" class="form-control" autocomplete="new-password" minlength="8" required></div>
                    <button id="rp-submit" type="submit" class="btn btn-primary acct-btn">Update Password</button>
                </form>`,
                `<a href="#signin" class="acct-link">Back to sign in</a>`)
        };
    }

    function pageRewards() {
        return {
            title: 'Petals Rewards Loyalty Program | Petals Paradise Events',
            desc: 'Earn 10 points for every $1 on party rentals and decor. Redeem 500 points for $5 off your next order.',
            html: `
                <div class="container acct-page">
                    <div class="acct-hero">
                        <div class="acct-auth-badge">🌸 Petals Rewards</div>
                        <h1 class="acct-title acct-hero-title">Celebrate more. <span class="acct-gold">Save more.</span></h1>
                        <p class="acct-subtitle">Earn <strong>10 points for every $1</strong> you spend with us. Every <strong>500 points = $5 off</strong> your next order.</p>
                        <div class="acct-hero-btns">
                            ${state.loggedIn
                                ? `<a href="#profile" id="rewards-view-btn" class="btn btn-primary">View My Points (${fmt(balance())})</a>`
                                : `<a href="#signup" id="rewards-join-btn" class="btn btn-primary">Join Free</a><a href="#signin" id="rewards-signin-btn" class="btn btn-outline">Sign In</a>`}
                        </div>
                    </div>
                    ${howItWorks()}
                    <h2 class="section-title acct-section-title">Rewards FAQ</h2>
                    ${faqHtml()}
                </div>`
        };
    }

    function statusClass(s) {
        return ({ Completed: 'ok', Returned: 'ok', Delivered: 'ok', Cancelled: 'bad', Pending: 'wait' })[s] || 'info';
    }

    function pageProfile() {
        if (!state.loaded) {
            return { title: 'My Account | Petals Paradise Events', html: `<div class="container acct-page"><div class="acct-loading">Loading your account…</div></div>` };
        }
        if (!state.loggedIn) return pageSignin();
        const d = state.data;
        const bal = d.points.balance;
        const redeemable = d.points.redeemable;
        const toNext = bal < RULES.minRedeem ? RULES.minRedeem - bal : (RULES.step - (bal % RULES.step)) % RULES.step || RULES.step;
        const progress = bal < RULES.minRedeem ? (bal / RULES.minRedeem) * 100 : ((bal % RULES.step) / RULES.step) * 100;
        const typeLabel = { earn: 'Earned', redeem: 'Redeemed', refund: 'Refunded', adjust: 'Adjustment' };

        return {
            title: 'My Rewards & Orders | Petals Paradise Events',
            html: `
                <div class="container acct-page">
                    <div class="acct-profile-head">
                        <div>
                            <h1 class="acct-title">Hi, ${firstName()} 👋</h1>
                            <p class="acct-subtitle">${esc(d.customer.email)}</p>
                        </div>
                        <button id="acct-logout-btn" class="btn btn-outline" onclick="ppeAccountLogout()">Sign Out</button>
                    </div>

                    <div class="acct-points-card">
                        <div class="acct-points-main">
                            <div class="acct-points-label">Your points</div>
                            <div class="acct-points-value" id="acct-points-value">${fmt(bal)}</div>
                            <div class="acct-points-worth">${redeemable > 0 ? `${fmt(redeemable)} pts redeemable = <strong>${money(d.points.value_dollars)} off</strong>` : 'Reach 500 points to unlock $5 off'}</div>
                        </div>
                        <div class="acct-points-progress">
                            <div class="acct-progress-bar"><div class="acct-progress-fill" style="width:${Math.min(100, progress).toFixed(1)}%"></div></div>
                            <div class="acct-progress-text">${fmt(toNext)} more points to your ${bal < RULES.minRedeem ? 'first' : 'next'} $5 reward</div>
                        </div>
                        <a href="#rentals" id="acct-shop-btn" class="btn btn-primary">Shop Rentals</a>
                    </div>

                    <div class="acct-grid">
                        <section class="acct-panel">
                            <h2 class="acct-panel-title">My Orders</h2>
                            ${d.orders.length ? `<div class="acct-list">${d.orders.map(o => `
                                <div class="acct-row">
                                    <div><div class="acct-row-title">${esc(o.id)}</div>
                                        <div class="acct-row-sub">Event: ${esc(o.event_date || '—')} · ${esc(o.fulfillment_method || '')}</div></div>
                                    <div class="acct-row-right">
                                        <span class="acct-status acct-status-${statusClass(o.status)}">${esc(o.status)}</span>
                                        <div class="acct-row-sub">${money(o.total)}${Number(o.points_earned) > 0 ? ` · +${fmt(o.points_earned)} pts` : ''}${Number(o.points_redeemed) > 0 ? ` · −${fmt(o.points_redeemed)} pts` : ''}</div>
                                    </div>
                                </div>`).join('')}</div>`
                                : `<p class="acct-empty">No orders yet. <a href="#rentals" class="acct-link">Browse rentals</a> to get started!</p>`}
                        </section>
                        <section class="acct-panel">
                            <h2 class="acct-panel-title">Points History</h2>
                            ${d.history.length ? `<div class="acct-list">${d.history.map(h => `
                                <div class="acct-row">
                                    <div><div class="acct-row-title">${typeLabel[h.type] || esc(h.type)}</div>
                                        <div class="acct-row-sub">${esc(h.note || h.order_id || '')}</div></div>
                                    <div class="acct-pts ${Number(h.points) >= 0 ? 'plus' : 'minus'}">${Number(h.points) >= 0 ? '+' : ''}${fmt(h.points)}</div>
                                </div>`).join('')}</div>`
                                : `<p class="acct-empty">Points appear here once an order is Returned (pickup) or Completed (delivery).</p>`}
                        </section>
                    </div>

                    <h2 class="section-title acct-section-title">How Petals Rewards works</h2>
                    ${howItWorks()}
                </div>`
        };
    }

    window.renderAccountPage = function (page) {
        switch (page) {
            case 'signin': return pageSignin();
            case 'signup': return pageSignup();
            case 'forgot': return pageForgot();
            case 'reset': return pageReset();
            case 'rewards': return pageRewards();
            case 'profile': default: return pageProfile();
        }
    };

    // ── Form handlers ────────────────────────────────────────────────────
    function showMsg(text, ok) {
        const el = document.getElementById('acct-msg');
        if (!el) return;
        el.textContent = text;
        el.className = 'acct-msg ' + (ok ? 'ok' : 'err');
    }

    window.ppeAccountSubmit = async function (e, action) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const body = Object.fromEntries(new FormData(form));
        if (body.email !== undefined && !/^\S+@\S+\.\S+$/.test(body.email)) return showMsg('Please enter a valid email address.');
        if ((action === 'signup' || action === 'reset') && (body.password || '').length < 8) return showMsg('Password must be at least 8 characters.');
        if (action === 'signup' && !body.name?.trim()) return showMsg('Please enter your name.');

        const label = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Please wait…';
        try {
            const json = await api(action, body);
            if (json.error) { showMsg(json.error); return; }
            if (action === 'forgot') { showMsg(json.message || 'Check your email for a reset link.', true); return; }
            setSession(json);
            if (action === 'signup' && json.backfilled_points > 0) {
                sessionStorage.setItem('ppeWelcome', `Welcome! We added ${fmt(json.backfilled_points)} points from your past orders 🎉`);
            }
            const back = sessionStorage.getItem('ppeAfterLogin');
            sessionStorage.removeItem('ppeAfterLogin');
            window.location.hash = back || '#profile';
            if (typeof window.confetti === 'function' && action === 'signup') window.confetti({ particleCount: 120, spread: 70, origin: { y: 0.6 } });
        } catch (err) {
            showMsg('Network error. Please check your connection and try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = label;
        }
    };

    window.ppeAccountLogout = async function () {
        try { await api('logout', {}); } catch (e) {}
        setSession(null);
        window.ppeSelectedRedeem = 0;
        window.location.hash = '#';
    };

    window.ppeGoSignin = function (back) {
        sessionStorage.setItem('ppeAfterLogin', back || currentRoute());
        window.location.hash = '#signin';
    };

    // ── Cart / checkout integration ──────────────────────────────────────
    function orderBase() {
        if (!window.ppeCart) return 0;
        return Math.max(0, Number(window.ppeCart.subtotal()) - Number(window.ppeCart.discount()));
    }

    function maxRedeemable() {
        if (!state.loggedIn || !window.ppeCart) return 0;
        const capDollars = Number(window.ppeCart.subtotal()) * RULES.maxPct;
        const capPoints = Math.floor((capDollars * RULES.perDollarOff) / RULES.step) * RULES.step;
        const balPoints = Math.floor(balance() / RULES.step) * RULES.step;
        return Math.max(0, Math.min(capPoints, balPoints));
    }

    function renderEarnPreview() {
        const el = document.getElementById('loyalty-earn-preview');
        if (!el) return;
        const redeemDollars = (window.ppeSelectedRedeem || 0) / RULES.perDollarOff;
        const pts = Math.floor(Math.max(0, orderBase() - redeemDollars) * RULES.perDollar);
        el.innerHTML = state.loggedIn
            ? `<div class="acct-earn-note">⭐ You'll earn about <strong>${fmt(pts)} points</strong> on this order.</div>`
            : `<div class="acct-earn-note">⭐ Earn <strong>${fmt(pts)} points</strong> on this order — <a href="javascript:void(0)" onclick="ppeGoSignin()" class="acct-link">sign in</a> or <a href="#signup" class="acct-link">join free</a>.</div>`;
    }

    function updateCheckoutTotal() {
        const el = document.getElementById('checkout-total-amount');
        if (!el) return;
        const off = (window.ppeSelectedRedeem || 0) / RULES.perDollarOff;
        const total = Math.max(0, orderBase() - off);
        const delivery = window.ppeCart.fulfillment() === 'Delivery' ? ' + Delivery (TBD)' : '';
        el.textContent = `${money(total)}${delivery}`;
    }

    function renderRedeemBox() {
        const box = document.getElementById('loyalty-redeem-box');
        if (!box) return;
        if (!state.loggedIn) {
            window.ppeSelectedRedeem = 0;
            box.innerHTML = `<div class="acct-redeem acct-redeem-guest">
                🌸 <strong>Have reward points?</strong> <a href="javascript:void(0)" onclick="ppeGoSignin('#checkout')" class="acct-link">Sign in</a> to use them on this order.</div>`;
            return;
        }
        const bal = balance();
        const max = maxRedeemable();
        if (window.ppeSelectedRedeem > max) window.ppeSelectedRedeem = 0;
        if (bal < RULES.minRedeem || max < RULES.minRedeem) {
            box.innerHTML = `<div class="acct-redeem">🌸 You have <strong>${fmt(bal)} points</strong>. ${bal < RULES.minRedeem
                ? `Reach ${fmt(RULES.minRedeem)} to unlock $5 off.`
                : 'Add more items to use your points (points can cover up to 50% of an order).'}</div>`;
            return;
        }
        const opts = [0];
        for (let p = RULES.step; p <= max; p += RULES.step) opts.push(p);
        box.innerHTML = `<div class="acct-redeem">
            <label for="loyalty-redeem-select" class="acct-redeem-label">🌸 Use reward points <span>(${fmt(bal)} available)</span></label>
            <select id="loyalty-redeem-select" class="form-control" onchange="ppeSetRedeem(this.value)">
                ${opts.map(p => `<option value="${p}" ${p === window.ppeSelectedRedeem ? 'selected' : ''}>${p === 0 ? "Don't use points" : `${fmt(p)} points → ${money(p / RULES.perDollarOff)} off`}</option>`).join('')}
            </select>
            ${window.ppeSelectedRedeem > 0 ? `<div class="acct-redeem-applied">✓ ${money(window.ppeSelectedRedeem / RULES.perDollarOff)} off applied</div>` : ''}
        </div>`;
    }

    window.ppeSetRedeem = function (v) {
        window.ppeSelectedRedeem = parseInt(v, 10) || 0;
        renderRedeemBox();
        updateCheckoutTotal();
        renderEarnPreview();
    };

    function prefillCheckout() {
        if (!state.loggedIn) return;
        const c = state.data.customer;
        const set = (id, v) => { const el = document.getElementById(id); if (el && !el.value && v) el.value = v; };
        set('checkout-name', c.name);
        set('checkout-email', c.email);
        set('checkout-phone', c.phone);
    }

    function injectBanner() {
        const main = document.getElementById('main-content');
        if (!main || main.querySelector('.acct-rewards-banner')) return;
        const wrap = document.createElement('div');
        wrap.className = 'container acct-banner-wrap';
        wrap.innerHTML = rewardsBanner();
        main.prepend(wrap);
    }

    window.ppeAfterRender = function (route) {
        updateNav();
        if (route === '#' || route === '#rentals' || route === '#cart') injectBanner();
        if (route === '#cart') renderEarnPreview();
        if (route === '#checkout') {
            prefillCheckout();
            renderRedeemBox();
            updateCheckoutTotal();
            renderEarnPreview();
        }
        if (route === '#profile') {
            const w = sessionStorage.getItem('ppeWelcome');
            if (w) {
                sessionStorage.removeItem('ppeWelcome');
                const head = document.querySelector('.acct-profile-head');
                if (head) head.insertAdjacentHTML('afterend', `<div class="acct-msg ok acct-welcome">${esc(w)}</div>`);
            }
        }
    };

    window.ppeAccount = { refresh, state };

    // Initial session check (runs before/alongside app.js first render)
    document.addEventListener('DOMContentLoaded', refresh);
})();
