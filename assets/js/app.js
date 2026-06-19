const modal = document.getElementById('modal');
const modalForm = document.getElementById('modalForm');
const modalMsg = document.getElementById('modalMsg');
const modalService = document.getElementById('modalService');
const modalMasterId = document.getElementById('modalMasterId');
const leadForm = document.getElementById('leadForm');
const formMsg = document.getElementById('formMsg');
const promoForm = document.getElementById('promoForm');
const promoMsg = document.getElementById('promoMsg');
const burger = document.getElementById('burger');
const mainNav = document.getElementById('mainNav');
const mastersTrack = document.getElementById('mastersTrack');

function openModal(service = '', masterId = '') {
  if (!modal) return;
  modal.classList.add('is-open');
  document.body.classList.add('modal-open');
  modal.setAttribute('aria-hidden', 'false');
  if (modalService) modalService.value = service || '';
  if (modalMasterId) modalMasterId.value = masterId || '';
  if (modalMsg) {
    modalMsg.textContent = '';
    modalMsg.className = 'msg';
  }
  requestAnimationFrame(() => {
    const panel = modal.querySelector('.modal__panel');
    if (panel) panel.scrollTop = 0;
    modal.querySelector('input[name="name"]')?.focus({ preventScroll: true });
  });
}

function closeModal() {
  if (!modal) return;
  modal.classList.remove('is-open');
  document.body.classList.remove('modal-open');
  modal.setAttribute('aria-hidden', 'true');
  if (modalMsg) {
    modalMsg.textContent = '';
    modalMsg.className = 'msg';
  }
}

document.querySelectorAll('[data-open-modal]').forEach((button) => {
  button.addEventListener('click', () => openModal(button.dataset.service || '', button.dataset.masterId || ''));
});

document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModal));
document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });

burger?.addEventListener('click', () => {
  const isOpen = mainNav?.classList.toggle('is-open');
  burger.classList.toggle('is-open', Boolean(isOpen));
  burger.setAttribute('aria-expanded', String(Boolean(isOpen)));
});

mainNav?.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', () => {
    mainNav.classList.remove('is-open');
    burger?.classList.remove('is-open');
    burger?.setAttribute('aria-expanded', 'false');
  });
});

document.querySelector('[data-master-prev]')?.addEventListener('click', () => mastersTrack?.scrollBy({ left: -300, behavior: 'smooth' }));
document.querySelector('[data-master-next]')?.addEventListener('click', () => mastersTrack?.scrollBy({ left: 300, behavior: 'smooth' }));

function setMessage(el, text, type = '') {
  if (!el) return;
  el.innerHTML = text;
  el.className = type ? `msg msg--${type}` : 'msg';
}

function isValidEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
}

async function sendLead(formEl, msgEl) {
  if (!formEl || !msgEl) return;
  setMessage(msgEl, 'Отправка заявки...', 'info');
  const submit = formEl.querySelector('button[type="submit"]');
  if (submit) submit.disabled = true;

  const fd = new FormData(formEl);
  const payload = Object.fromEntries(fd.entries());
  const name = String(payload.name || '').trim();
  const phone = String(payload.phone || '').trim();
  const email = String(payload.email || '').trim();
  const service = String(payload.service || '').trim();

  if (!name || !phone || !email || !service) {
    setMessage(msgEl, 'Заполните имя, телефон, email и услугу.', 'error');
    if (submit) submit.disabled = false;
    return;
  }
  if (!isValidEmail(email)) {
    setMessage(msgEl, 'Введите корректный email — на него придёт код заявки.', 'error');
    if (submit) submit.disabled = false;
    return;
  }
  if (formEl.querySelector('input[name="privacy"]') && String(payload.privacy || '') !== '1') {
    setMessage(msgEl, 'Подтвердите согласие на обработку персональных данных.', 'error');
    if (submit) submit.disabled = false;
    return;
  }

  try {
    const res = await fetch('api/lead_create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data?.ok) {
      setMessage(msgEl, data?.error || 'Не удалось отправить заявку. Попробуйте ещё раз.', 'error');
      return;
    }
    const link = data.client_url ? `<a href="${data.client_url}">открыть кабинет заявки</a>` : '';
    setMessage(msgEl, `Заявка №${data.id} принята. Код клиента: <b>${data.access_code}</b>. Код и ссылка отправлены на email. ${link}`, 'success');
    formEl.reset();
    updateCharCounters(formEl);
    if (modalMasterId) modalMasterId.value = '';
  } catch (error) {
    setMessage(msgEl, 'Ошибка соединения. Попробуйте ещё раз или позвоните по номеру сайта.', 'error');
  } finally {
    if (submit) submit.disabled = false;
  }
}

modalForm?.addEventListener('submit', (event) => { event.preventDefault(); sendLead(modalForm, modalMsg); });
leadForm?.addEventListener('submit', (event) => { event.preventDefault(); sendLead(leadForm, formMsg); });
promoForm?.addEventListener('submit', (event) => { event.preventDefault(); sendLead(promoForm, promoMsg); });

document.querySelectorAll('input[type="tel"]').forEach((input) => {
  input.addEventListener('input', () => { input.value = input.value.replace(/[^0-9+()\-\s]/g, '').slice(0, 20); });
});

function updateCharCounters(root = document) {
  root.querySelectorAll('textarea[maxlength][data-count-target]').forEach((textarea) => {
    const target = document.getElementById(textarea.dataset.countTarget || '');
    if (!target) return;
    const max = Number(textarea.getAttribute('maxlength') || 0);
    target.textContent = `${textarea.value.length}/${max}`;
  });
}

document.querySelectorAll('textarea[maxlength][data-count-target]').forEach((textarea) => {
  textarea.addEventListener('input', () => updateCharCounters(document));
});
updateCharCounters(document);
