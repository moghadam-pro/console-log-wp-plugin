(function () {
  'use strict';

  const form = document.querySelector('.mpro-console-admin__form');
  const preview = document.querySelector('.mpro-console-log--preview');
  if (!form || !preview) return;

  const lineData = preview.querySelector('.mpro-console-log__lines');
  const settingsData = preview.querySelector('.mpro-console-log__settings');
  const logsField = document.getElementById('mpro-console-logs');
  const countNode = document.querySelector('[data-mpro-line-count]');
  let debounceTimer = null;

  function field(key) {
    return form.querySelector(`[data-mpro-setting="${key}"]`);
  }

  function numeric(key, fallback) {
    const node = field(key);
    const value = node ? Number(node.value) : fallback;
    return Number.isFinite(value) ? value : fallback;
  }

  function checked(key) {
    const node = field(key);
    return Boolean(node && node.checked);
  }

  function lines() {
    return String(logsField.value || '')
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter(Boolean);
  }

  function updatePreview() {
    const currentLines = lines();
    const colorField = field('text_color_slug');
    const selectedColor = colorField && colorField.selectedOptions.length
      ? (colorField.selectedOptions[0].getAttribute('data-color') || 'currentColor')
      : 'currentColor';

    lineData.textContent = JSON.stringify(currentLines);
    settingsData.textContent = JSON.stringify({
      baseInterval: numeric('base_interval', 760),
      variation: numeric('variation', 52),
      scrollDuration: numeric('scroll_duration', 520),
      rhythm: field('rhythm') ? field('rhythm').value : 'organic',
      easing: field('easing') ? field('easing').value : 'ease-in-out-cubic',
      maxLines: numeric('max_lines', 25),
      showTimestamp: checked('show_timestamp'),
      showCategory: checked('show_category'),
      loop: checked('loop')
    });

    preview.style.setProperty('--mpro-console-width', field('width') ? field('width').value : '100%');
    preview.style.setProperty('--mpro-console-height', field('height') ? field('height').value : '220px');
    preview.style.setProperty('--mpro-console-font-size', field('font_size') ? field('font_size').value : '11px');
    preview.style.setProperty('--mpro-console-line-height', numeric('line_height', 1.45));
    preview.style.setProperty('--mpro-console-color', selectedColor);
    preview.style.setProperty('--mpro-console-opacity', numeric('text_opacity', .55));
    preview.classList.toggle('mpro-console-log--masked', checked('mask_fade'));

    if (countNode) countNode.textContent = currentLines.length;
    preview.dispatchEvent(new CustomEvent('mpro-console-restart'));
  }

  function debouncedUpdate() {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(updatePreview, 170);
  }

  form.addEventListener('input', debouncedUpdate);
  form.addEventListener('change', updatePreview);

  const restart = document.querySelector('[data-mpro-restart]');
  if (restart) restart.addEventListener('click', updatePreview);

  document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      const text = button.getAttribute('data-copy') || '';
      try {
        await navigator.clipboard.writeText(text);
      } catch (error) {
        const temporary = document.createElement('textarea');
        temporary.value = text;
        document.body.appendChild(temporary);
        temporary.select();
        document.execCommand('copy');
        temporary.remove();
      }
      const label = button.querySelector('span');
      if (!label) return;
      const original = label.textContent;
      label.textContent = 'Copied';
      window.setTimeout(() => { label.textContent = original; }, 1100);
    });
  });
})();
