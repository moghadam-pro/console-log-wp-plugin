(function () {
  'use strict';

  const EASINGS = {
    'ease-in-out': 'ease-in-out',
    'ease-in-out-sine': 'cubic-bezier(0.37, 0, 0.63, 1)',
    'ease-in-out-quad': 'cubic-bezier(0.45, 0, 0.55, 1)',
    'ease-in-out-cubic': 'cubic-bezier(0.65, 0, 0.35, 1)',
    'ease-in-out-quart': 'cubic-bezier(0.76, 0, 0.24, 1)',
    'ease-in-out-quint': 'cubic-bezier(0.83, 0, 0.17, 1)',
    'ease-in-out-back': 'cubic-bezier(0.68, -0.35, 0.32, 1.35)'
  };

  const EASING_KEYS = Object.keys(EASINGS);

  function readJson(element, selector, fallback) {
    const node = element.querySelector(selector);
    if (!node) return fallback;
    try {
      return JSON.parse(node.textContent || '');
    } catch (error) {
      return fallback;
    }
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function parseLog(rawLine) {
    const line = String(rawLine == null ? '' : rawLine).trim();
    const separator = line.indexOf('|');
    if (separator > 0) {
      return {
        category: line.slice(0, separator).trim(),
        message: line.slice(separator + 1).trim()
      };
    }
    return { category: '', message: line };
  }

  function timestamp() {
    const now = new Date();
    const base = now.toLocaleTimeString('en-GB', {
      hour12: false,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
    return base + '.' + String(now.getMilliseconds()).padStart(3, '0').slice(0, 2);
  }

  class MPROConsoleLog {
    constructor(element) {
      this.element = element;
      this.track = element.querySelector('.mpro-console-log__track');
      this.timer = null;
      this.index = 0;
      this.tick = 0;
      this.burstRemaining = 0;
      this.isPaused = false;
      this.reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      this.onRestart = this.restart.bind(this);
      this.onVisibility = this.handleVisibility.bind(this);
      this.observer = null;

      this.element.addEventListener('mpro-console-restart', this.onRestart);
      document.addEventListener('visibilitychange', this.onVisibility);
      this.observeVisibility();
      this.restart();
    }

    readState() {
      this.lines = readJson(this.element, '.mpro-console-log__lines', []).filter(Boolean);
      const settings = readJson(this.element, '.mpro-console-log__settings', {});
      this.settings = {
        baseInterval: clamp(Number(settings.baseInterval) || 760, 80, 10000),
        variation: clamp(Number(settings.variation) || 0, 0, 95) / 100,
        scrollDuration: clamp(Number(settings.scrollDuration) || 0, 0, 5000),
        rhythm: settings.rhythm || 'organic',
        easing: settings.easing || 'ease-in-out-cubic',
        maxLines: clamp(Number(settings.maxLines) || 25, 2, 200),
        showTimestamp: settings.showTimestamp !== false,
        showCategory: settings.showCategory !== false,
        loop: settings.loop !== false
      };
    }

    restart() {
      window.clearTimeout(this.timer);
      this.readState();
      this.index = 0;
      this.tick = 0;
      this.burstRemaining = 0;
      if (this.track) this.track.replaceChildren();
      if (!this.lines.length || this.isPaused) return;
      this.timer = window.setTimeout(() => this.addNext(), 80);
    }

    observeVisibility() {
      if (!('IntersectionObserver' in window)) return;
      this.observer = new IntersectionObserver((entries) => {
        const visible = entries.some((entry) => entry.isIntersecting);
        if (visible && this.isPaused && !document.hidden) {
          this.isPaused = false;
          this.scheduleNext();
        } else if (!visible) {
          this.isPaused = true;
          window.clearTimeout(this.timer);
        }
      }, { rootMargin: '160px' });
      this.observer.observe(this.element);
    }

    handleVisibility() {
      if (document.hidden) {
        this.isPaused = true;
        window.clearTimeout(this.timer);
      } else {
        this.isPaused = false;
        this.scheduleNext();
      }
    }

    getEasing() {
      if (this.settings.easing === 'random') {
        const key = EASING_KEYS[Math.floor(Math.random() * EASING_KEYS.length)];
        return EASINGS[key];
      }
      return EASINGS[this.settings.easing] || EASINGS['ease-in-out-cubic'];
    }

    getDelay() {
      const base = this.settings.baseInterval;
      const variance = this.settings.variation;
      let factor = 1;

      if (this.settings.rhythm === 'breathing') {
        const phase = (Math.sin(this.tick * 0.72 - Math.PI / 2) + 1) / 2;
        const eased = phase < 0.5
          ? 2 * phase * phase
          : 1 - Math.pow(-2 * phase + 2, 2) / 2;
        factor = 1 + ((eased * 2) - 1) * variance;
        factor += (Math.random() - 0.5) * variance * 0.18;
      } else if (this.settings.rhythm === 'bursts') {
        if (this.burstRemaining <= 0) {
          if (Math.random() < 0.72) {
            this.burstRemaining = 2 + Math.floor(Math.random() * 4);
            factor = 1 - variance * (0.55 + Math.random() * 0.35);
          } else {
            factor = 1 + variance * (1.15 + Math.random() * 1.2);
          }
        } else {
          this.burstRemaining -= 1;
          factor = 1 - variance * (0.45 + Math.random() * 0.45);
        }
      } else if (this.settings.rhythm === 'random') {
        factor = 0.35 + Math.random() * (1.3 + variance * 1.8);
      } else {
        const random = (Math.random() * 2) - 1;
        const easedRandom = Math.sign(random) * (1 - Math.pow(1 - Math.abs(random), 3));
        factor = 1 + easedRandom * variance;
      }

      this.tick += 1;
      return Math.max(80, Math.round(base * factor));
    }

    createLine(rawLine) {
      const parsed = parseLog(rawLine);
      const line = document.createElement('div');
      line.className = 'mpro-console-log__line';

      if (this.settings.showTimestamp) {
        const time = document.createElement('span');
        time.className = 'mpro-console-log__timestamp';
        time.textContent = '[' + timestamp() + ']';
        line.appendChild(time);
      }

      if (this.settings.showCategory && parsed.category) {
        const category = document.createElement('span');
        category.className = 'mpro-console-log__category';
        category.textContent = '[' + parsed.category + ']';
        line.appendChild(category);
      }

      const message = document.createElement('span');
      message.className = 'mpro-console-log__message';
      message.textContent = parsed.message;
      line.appendChild(message);
      return line;
    }

    addNext() {
      if (this.isPaused || !this.track || !this.lines.length) return;

      if (this.index >= this.lines.length) {
        if (!this.settings.loop) return;
        this.index = 0;
      }

      const existingLines = Array.from(this.track.children);
      const before = new Map(existingLines.map((line) => [line, line.getBoundingClientRect().top]));
      const newLine = this.createLine(this.lines[this.index]);
      this.track.appendChild(newLine);
      this.index += 1;

      const duration = this.reducedMotion ? 0 : this.settings.scrollDuration;
      const easing = this.getEasing();

      existingLines.forEach((line) => {
        const previousTop = before.get(line);
        const currentTop = line.getBoundingClientRect().top;
        const delta = previousTop - currentTop;
        if (duration > 0 && Math.abs(delta) > 0.1) {
          line.animate(
            [{ transform: `translateY(${delta}px)` }, { transform: 'translateY(0)' }],
            { duration, easing, fill: 'both' }
          );
        }
      });

      if (duration > 0) {
        newLine.animate(
          [
            { opacity: 0, transform: 'translateY(0.85em)' },
            { opacity: 1, transform: 'translateY(0)' }
          ],
          { duration: Math.max(180, Math.min(duration, 900)), easing, fill: 'both' }
        );
      }

      while (this.track.children.length > this.settings.maxLines) {
        this.track.removeChild(this.track.firstElementChild);
      }

      this.scheduleNext();
    }

    scheduleNext() {
      window.clearTimeout(this.timer);
      if (this.isPaused || !this.lines.length) return;
      this.timer = window.setTimeout(() => this.addNext(), this.getDelay());
    }

    destroy() {
      window.clearTimeout(this.timer);
      this.element.removeEventListener('mpro-console-restart', this.onRestart);
      document.removeEventListener('visibilitychange', this.onVisibility);
      if (this.observer) this.observer.disconnect();
    }
  }

  function mount(root) {
    const scope = root || document;
    scope.querySelectorAll('.mpro-console-log').forEach((element) => {
      if (element.mproConsoleInstance) return;
      element.mproConsoleInstance = new MPROConsoleLog(element);
    });
  }

  window.MPROConsoleLog = { mount, MPROConsoleLog };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => mount(document));
  } else {
    mount(document);
  }
})();
