/* =====================================================================
   Application behaviour: navigation drawer, menus, modals, confirm
   prompts and the "New Call" flow. No framework, no build step.
   ===================================================================== */
(function () {
  'use strict';

  var body = document.body;
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';

  // ------------------------------------------------------------ Drawer
  function setNav(open) {
    body.classList.toggle('nav-open', open);
    var btn = document.querySelector('[data-nav-open]');
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-nav-open]')) { setNav(true); return; }
    if (e.target.closest('[data-nav-close]')) { setNav(false); return; }
    if (e.target.closest('.shell-nav a') && window.innerWidth < 1024) setNav(false);
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth >= 1024) setNav(false);
  });

  // --------------------------------------------------------- Dropdowns
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-dropdown]');
    var openMenus = document.querySelectorAll('.dropdown.open');

    openMenus.forEach(function (menu) {
      if (!toggle || menu.id !== toggle.getAttribute('data-dropdown')) {
        menu.classList.remove('open');
      }
    });

    if (toggle) {
      var menu = document.getElementById(toggle.getAttribute('data-dropdown'));
      if (menu) {
        var open = !menu.classList.contains('open');
        menu.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    }
  });

  // ------------------------------------------------------------ Modals
  var lastFocus = null;

  function openModal(modal) {
    if (!modal) return;
    lastFocus = document.activeElement;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    body.classList.add('modal-open');
    var first = modal.querySelector('input:not([type=hidden]), select, textarea, button');
    if (first) setTimeout(function () { first.focus(); }, 60);
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    if (!document.querySelector('.modal.open')) body.classList.remove('modal-open');
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  window.AppModal = { open: openModal, close: closeModal };

  document.addEventListener('click', function (e) {
    var opener = e.target.closest('[data-modal-open]');
    if (opener) {
      e.preventDefault();
      openModal(document.getElementById(opener.getAttribute('data-modal-open')));
      return;
    }
    var closer = e.target.closest('[data-modal-close]');
    if (closer) {
      e.preventDefault();
      closeModal(closer.closest('.modal'));
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var open = document.querySelectorAll('.modal.open');
    if (open.length) { closeModal(open[open.length - 1]); return; }
    document.querySelectorAll('.dropdown.open').forEach(function (m) { m.classList.remove('open'); });
    setNav(false);
  });

  // ---------------------------------------------------- Confirm forms
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) e.preventDefault();
  });

  // --------------------------------------------------- Client tabs
  document.querySelectorAll('[data-tabs]').forEach(function (group) {
    var scope = group.getAttribute('data-tabs');
    var buttons = group.querySelectorAll('[data-tab]');
    var panels = document.querySelectorAll('[data-tab-panel][data-tab-scope="' + scope + '"]');

    function show(name) {
      buttons.forEach(function (b) {
        var on = b.getAttribute('data-tab') === name;
        b.classList.toggle('active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      panels.forEach(function (p) { p.hidden = p.getAttribute('data-tab-panel') !== name; });
    }

    buttons.forEach(function (b) {
      b.addEventListener('click', function () {
        show(b.getAttribute('data-tab'));
        try { history.replaceState(null, '', '#' + b.getAttribute('data-tab')); } catch (err) { /* ignore */ }
      });
    });

    var fromHash = location.hash.replace('#', '');
    if (fromHash && group.querySelector('[data-tab="' + fromHash + '"]')) show(fromHash);
  });

  // ------------------------------------------------------ Call forms
  // Every call form posts to our own endpoint only. The browser never sees
  // the voice platform's URL, key or payload shape.
  function newKey() {
    return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random();
  }

  function showCallAlert(box, tone, message) {
    if (!box) return;
    box.className = 'callout callout-' + tone + ' mb-4';
    box.innerHTML = '';
    var icon = document.createElement('i');
    icon.className = tone === 'success' ? 'icon-circle-check' : 'icon-circle-alert';
    var text = document.createElement('div');
    text.textContent = message;
    box.appendChild(icon);
    box.appendChild(text);
    box.hidden = false;
  }

  function bindCallForm(form) {
    var box = form.querySelector('[data-call-alert]');
    var submit = form.querySelector('[type="submit"]');
    form._key = newKey();

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (box) box.hidden = true;

      submit.disabled = true;
      var original = submit.innerHTML;
      submit.innerHTML = '<i class="icon-loader-circle spin"></i> Starting…';

      var data = new FormData(form);
      data.append('idempotency_key', form._key);

      try {
        var res = await fetch(form.getAttribute('data-call-form'), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: data,
        });
        var json = await res.json().catch(function () { return {}; });

        if (res.ok && json.ok) {
          showCallAlert(box, 'success', json.message || 'Call started.');
          form._key = newKey();
          setTimeout(function () { window.location.reload(); }, 1300);
          return;
        }

        if (res.status === 422 && json.errors) {
          var messages = [];
          Object.keys(json.errors).forEach(function (k) { messages = messages.concat(json.errors[k]); });
          showCallAlert(box, 'danger', messages.join(' '));
        } else {
          showCallAlert(box, 'danger', json.message || 'The call could not be started.');
        }
      } catch (err) {
        showCallAlert(box, 'danger', 'Network error. Please try again.');
      } finally {
        submit.disabled = false;
        submit.innerHTML = original;
      }
    });
  }

  document.querySelectorAll('form[data-call-form]').forEach(bindCallForm);

  // Open the shared New Call modal from anywhere, optionally pre-filled.
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-new-call]');
    if (!trigger || trigger.disabled) return;

    var modal = document.getElementById('newCallModal');
    var form = modal && modal.querySelector('form');
    if (!form) return;

    e.preventDefault();
    form.reset();
    form._key = newKey();
    var box = form.querySelector('[data-call-alert]');
    if (box) box.hidden = true;

    var d = trigger.dataset;
    var set = function (name, value) {
      var el = form.querySelector('[name="' + name + '"]');
      if (el) el.value = value || '';
    };

    set('customer_id', d.customerId);
    set('name', d.name);
    set('phone_number', d.phone);
    set('policy_number', d.policy);
    set('registered_mobile', d.registeredMobile);
    if (d.language) set('language', d.language);
    if (d.agentId) set('agent_id', d.agentId);

    var who = modal.querySelector('[data-call-who]');
    if (who) {
      who.hidden = !d.customerId;
      who.textContent = d.customerId ? 'Calling an existing customer: ' + (d.name || d.phone) : '';
    }

    openModal(modal);
  });

  // ----------------------------------------------------- Chart helper
  window.AppCharts = {
    palette: ['#65B82E', '#00A89D', '#2a78d6', '#eda100', '#e34948', '#7a5af8', '#e87ba4', '#98a2b3'],
    defaults: function () {
      if (!window.Chart) return;
      Chart.defaults.font.family = '"Inter", system-ui, sans-serif';
      Chart.defaults.font.size = 12;
      Chart.defaults.color = '#667085';
      Chart.defaults.borderColor = '#e7ece9';
      Chart.defaults.plugins.legend.display = false;
      Chart.defaults.plugins.tooltip.backgroundColor = '#101828';
      Chart.defaults.plugins.tooltip.padding = 10;
      Chart.defaults.plugins.tooltip.cornerRadius = 8;
      Chart.defaults.plugins.tooltip.boxPadding = 4;
      Chart.defaults.maintainAspectRatio = false;
    },
  };
})();
