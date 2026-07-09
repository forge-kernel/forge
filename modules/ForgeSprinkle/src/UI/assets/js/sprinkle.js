(function () {
  'use strict'

  /* ── registry ── */

  var directives = []
  var attrIndex = {}
  var initialized = false

  function matchNode(n) {
    for (var i = 0; i < directives.length; i++) {
      try {
        if (n.matches(directives[i][0])) {
          directives[i][1](n)
        }
      } catch (e) {
        console.error('ForgeSprinkle:', e)
      }
    }
  }

  function hasSprinkleAttr(n) {
    if (!n.hasAttributes) return false
    var attrs = n.attributes
    for (var i = 0; i < attrs.length; i++) {
      if (attrIndex[attrs[i].name]) return true
    }
    return false
  }

  function svgPath() {
    var m = document.querySelector('meta[name="sprinkle-svg-path"]')
    return m ? m.getAttribute('content') : '/assets/svg/'
  }

  window.ForgeSprinkle = {
    register: function (selector, handler) {
      directives.push([selector, handler])
      var m = selector.match(/\[([\w-]+)]/g)
      if (m) {
        for (var i = 0; i < m.length; i++) {
          attrIndex[m[i].slice(1, -1)] = true
        }
      }
      if (initialized) {
        document.querySelectorAll(selector).forEach(function (el) {
          try { handler(el) } catch (e) { console.error('ForgeSprinkle:', e) }
        })
      }
    }
  }

  /* ── 1. autosize ── */

  ForgeSprinkle.register('textarea[autosize]', function autosize(el) {
    function fit() {
      el.style.height = 'auto'
      el.style.height = el.scrollHeight + 'px'
    }
    el.addEventListener('input', fit)
    fit()
  })

  /* ── 2. sticky ── */

  ForgeSprinkle.register('[sticky]', function sticky(el) {
    if (el._sprinkleStickyInit) return
    el._sprinkleStickyInit = true
    var s = document.createElement('div')
    s.className = 'sprinkle-sticky-sentinel'
    if (!el.parentNode) return
    el.parentNode.insertBefore(s, el)
    new IntersectionObserver(function (e) {
      var stuck = !e[0].isIntersecting
      el.classList.toggle('sprinkle-stuck', stuck)
      el.toggleAttribute('data-sprinkle-stuck', stuck)
    }, { threshold: [0] }).observe(s)
  })

  /* ── 3. copy ── */

  function getContent(el) {
    return el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' ? el.value : el.textContent
  }

  var liveRegion
  function ensureLive() {
    if (!liveRegion) {
      liveRegion = document.createElement('div')
      liveRegion.setAttribute('aria-live', 'polite')
      liveRegion.className = 'sprinkle-sr-only'
      document.body.appendChild(liveRegion)
    }
  }
  function showCopied(el) {
    el.classList.add('sprinkle-copied')
    ensureLive()
    liveRegion.textContent = 'Copied'
    setTimeout(function () { el.classList.remove('sprinkle-copied') }, 1500)
  }

  ForgeSprinkle.register('[copy]', function copy(el) {
    el.addEventListener('click', function () {
      var sel = el.getAttribute('copy')
      var t = sel ? document.querySelector(sel) : el
      if (!t) return
      navigator.clipboard.writeText(getContent(t)).then(function () {
        showCopied(el)
      }).catch(function () {})
    })
  })

  /* ── 4. zoomable ── */

  ForgeSprinkle.register('img[zoomable]', function zoomable(el) {
    el.addEventListener('click', function () {
      var bg = document.createElement('div')
      bg.className = 'sprinkle-zoomed-bg'
      bg.addEventListener('click', function () { bg.remove() })
      var img = document.createElement('img')
      img.src = el.src
      img.alt = el.alt
      img.className = 'sprinkle-zoomed-img'
      bg.appendChild(img)
      document.body.appendChild(bg)
    })
  })

  /* ── 5. confirm-leave ── */

  ForgeSprinkle.register('form[confirm-leave]', function confirmLeave(el) {
    var dirty = false
    el.addEventListener('input', function () { dirty = true })
    el.addEventListener('submit', function () { dirty = false })
    window.addEventListener('beforeunload', function (e) {
      if (!dirty) return
      e.preventDefault()
      e.returnValue = ''
    })
  })

  /* ── 6. loading ── */

  ForgeSprinkle.register('button[loading]', function loading(el) {
    function set() {
      el.classList.add('sprinkle-loading')
      el.disabled = true
      el.setAttribute('aria-busy', 'true')
    }
    el.addEventListener('click', function () {
      if (!el.type || el.type === 'submit') {
        set()
      }
    })
    if (el.form) {
      el.form.addEventListener('submit', set)
    }
  })

  /* ── 7. character-count ── */



  ForgeSprinkle.register('[character-count]', function characterCount(el) {
    var max = el.getAttribute('maxlength')
    var c = document.createElement('span')
    c.className = 'sprinkle-char-count'
    c.setAttribute('aria-live', 'polite')
    if (!el.parentNode) return
    var wrap = el.parentNode.classList.contains('sprinkle-enhance-wrap')
      ? el.parentNode
      : null
    if (!wrap) {
      wrap = document.createElement('span')
      wrap.className = 'sprinkle-enhance-wrap'
      el.parentNode.insertBefore(wrap, el)
      wrap.appendChild(el)
    }
    wrap.appendChild(c)
    function update() {
      var len = el.value.length
      c.textContent = max ? len + ' / ' + max : '' + len
    }
    el.addEventListener('input', update)
    update()
  })

  /* ── 8. auto-select ── */

  ForgeSprinkle.register('input[auto-select]', function autoSelect(el) {
    el.addEventListener('focus', function () { el.select() })
  })

  /* ── 9. truncate ── */

  ForgeSprinkle.register('[truncate]', function truncate(el) {
    var lines = parseInt(el.getAttribute('truncate'), 10) || 3
    el.style.maxHeight = 'none'
    var fullHeight = el.scrollHeight
    var lh = parseFloat(getComputedStyle(el).lineHeight)
    if (!lh || isNaN(lh)) lh = parseFloat(getComputedStyle(el).fontSize) * 1.4
    var clamped = Math.ceil(lh * lines)
    el.style.maxHeight = ''
    el.classList.add('sprinkle-truncated')
    if (fullHeight <= clamped + 1) return
    el.style.maxHeight = clamped + 'px'
    var btn = document.createElement('button')
    btn.type = 'button'
    btn.className = 'sprinkle-truncate-btn'
    btn.textContent = 'Show more'
    btn.setAttribute('aria-expanded', 'false')
    btn.addEventListener('click', function () {
      var open = el.classList.toggle('sprinkle-expanded')
      btn.textContent = open ? 'Show less' : 'Show more'
      btn.setAttribute('aria-expanded', open)
      el.style.maxHeight = open ? fullHeight + 'px' : clamped + 'px'
    })
    el.after(btn)
  })

  /* ── 10. enter-submit ── */

  ForgeSprinkle.register('textarea[enter-submit]', function enterSubmit(el) {
    el.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        var form = el.closest('form')
        if (form) form.requestSubmit()
      }
    })
  })

  /* ── 11. file-name ── */

  ForgeSprinkle.register('input[file-name]', function enhanceFile(el) {
    if (el.getAttribute('type') !== 'file') return
    if (!el.parentNode) return
    var info = document.createElement('span')
    info.className = 'sprinkle-file-info'
    el.parentNode.insertBefore(info, el.nextSibling)
    function update() {
      if (el.files.length === 0) {
        info.textContent = ''
        return
      }
      var parts = []
      for (var i = 0; i < el.files.length; i++) {
        parts.push(el.files[i].name)
      }
      info.textContent = parts.join(', ')
    }
    el.addEventListener('change', update)
  })

  /* ── 12. switch ── */

  ForgeSprinkle.register('input[type="checkbox"][switch]', function switchCheck(el) {
    if (el._sprinkleSwitchInit) return
    el._sprinkleSwitchInit = true
    if (!el.parentNode) return
    var wrap = document.createElement('span')
    wrap.className = 'sprinkle-switch-wrap'
    el.parentNode.insertBefore(wrap, el)
    wrap.appendChild(el)
    var track = document.createElement('span')
    track.className = 'sprinkle-switch-track'
    track.setAttribute('role', 'switch')
    track.setAttribute('aria-checked', el.checked)
    wrap.appendChild(track)
    function sync() {
      track.setAttribute('aria-checked', el.checked)
      track.classList.toggle('sprinkle-switch-on', el.checked)
    }
    track.addEventListener('click', function (e) {
      e.stopPropagation()
      el.click()
    })
    el.addEventListener('change', sync)
    sync()
  })

  /* ── 13. leading / suffix icons ── */

  ForgeSprinkle.register('input[leading], input[suffix], input[clearable]', function enhanceIcons(el) {
    if (!el.parentNode) return
    var wrap = el.parentNode.classList.contains('sprinkle-enhance-wrap')
      ? el.parentNode
      : null
    if (!wrap) {
      wrap = document.createElement('span')
      wrap.className = 'sprinkle-enhance-wrap'
      el.parentNode.insertBefore(wrap, el)
      wrap.appendChild(el)
    }
    var path = svgPath()
    var lead = el.getAttribute('leading')
    if (lead) {
      var l = document.createElement('img')
      l.src = path + lead + '.svg'
      l.className = 'sprinkle-icon-leading'
      l.alt = ''
      wrap.insertBefore(l, el)
    }
    var suff = el.getAttribute('suffix')
    var clear = el.hasAttribute('clearable')
    var pw = el.getAttribute('type') === 'password'
    var srch = el.getAttribute('type') === 'search'
    var isToggle = pw && !clear
    var isClear = clear || srch || suff === 'close' || suff === 'clear'
    var iconName = suff
    if (!iconName && (clear || srch)) iconName = 'close'
    if (clear) iconName = 'close'
    if (iconName) {
      if (isToggle || isClear) {
        var btn = document.createElement('button')
        btn.type = 'button'
        btn.className = 'sprinkle-icon-suffix sprinkle-icon-interactive'
        var img = document.createElement('img')
        img.alt = ''
        btn.appendChild(img)
        if (isToggle) {
          btn.setAttribute('aria-label', 'Show password')
          img.src = path + suff + '.svg'
          btn.addEventListener('click', function () {
            var show = el.type === 'password'
            el.type = show ? 'text' : 'password'
            img.src = path + (show ? suff + '-off' : suff) + '.svg'
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password')
            if (!show) el.focus()
          })
        } else {
          btn.setAttribute('aria-label', 'Clear input')
          img.src = path + iconName + '.svg'
          btn.addEventListener('click', function () {
            el.value = ''
            el.dispatchEvent(new Event('input', { bubbles: true }))
            el.focus()
          })
        }
        wrap.appendChild(btn)
      } else {
        var s = document.createElement('img')
        s.className = 'sprinkle-icon-suffix'
        s.alt = ''
        s.src = path + iconName + '.svg'
        wrap.appendChild(s)
      }
    }
  })

  /* ── 14. auto-width ── */
  /* handled entirely by CSS: input[auto-width] { width:100%; box-sizing:border-box } */

  /* ── 15. accordion ── */

  ForgeSprinkle.register('details[accordion]', function accordion(el) {
    if (el._sprinkleAccordionInit) return
    el._sprinkleAccordionInit = true
    el.classList.add('sprinkle-accordion')
    if (!el.parentNode) return
    var body = document.createElement('div')
    body.className = 'sprinkle-accordion-body'
    Array.from(el.children).forEach(function (c) {
      if (c.tagName === 'SUMMARY') return
      body.appendChild(c)
    })
    el.appendChild(body)
    var summary = el.querySelector('summary')
    if (!summary) return
    summary.addEventListener('click', function (e) {
      e.preventDefault()
      if (el._sprinkleAnimating) return
      var group = el.getAttribute('accordion')
      if (group && !el.open) {
        var siblings = document.querySelectorAll('details[accordion="' + group + '"].sprinkle-accordion[open]')
        for (var i = 0; i < siblings.length; i++) {
          if (siblings[i] !== el && siblings[i]._sprinkleClose) siblings[i]._sprinkleClose()
        }
      }
      if (el.open) el._sprinkleClose()
      else el._sprinkleOpen()
    })
    function animEnd() {
      el._sprinkleAnimating = false
      body.style.height = ''
    }
    el._sprinkleOpen = function () {
      if (!el.parentNode) return
      el._sprinkleAnimating = true
      el.setAttribute('open', '')
      summary.setAttribute('aria-expanded', 'true')
      body.style.height = '0px'
      requestAnimationFrame(function () {
        var h = body.scrollHeight
        if (h === 0) {
          el._sprinkleAnimating = false
          return
        }
        body.style.height = h + 'px'
        body.addEventListener('transitionend', animEnd, { once: true })
      })
    }
    el._sprinkleClose = function () {
      if (!el.parentNode) return
      el._sprinkleAnimating = true
      summary.setAttribute('aria-expanded', 'false')
      body.style.height = body.scrollHeight + 'px'
      requestAnimationFrame(function () {
        body.style.height = '0px'
        body.addEventListener('transitionend', function closeEnd() {
          el.removeAttribute('open')
          animEnd()
        }, { once: true })
      })
    }
    var initOpen = el.hasAttribute('open')
    summary.setAttribute('aria-expanded', initOpen ? 'true' : 'false')
    if (initOpen) {
      body.style.height = ''
    }
  })

  /* ── 16. close-outside ── */

  ForgeSprinkle.register('[close-outside]', function closeOutside(el) {
    document.addEventListener('click', function (e) {
      if (!el.open) return
      if (el.tagName === 'DIALOG') {
        if (el.contains(e.target) && el !== e.target) return
        el.close()
      } else {
        if (el.contains(e.target)) return
        if (el._sprinkleClose) el._sprinkleClose()
        else el.removeAttribute('open')
      }
    }, true)
  })

  /* ── 17. open-group / close-group ── */

  ForgeSprinkle.register('[open-group], [close-group]', function groupToggle(el) {
    el.addEventListener('click', function () {
      var group = el.getAttribute('open-group') || el.getAttribute('close-group')
      var open = el.hasAttribute('open-group')
      var items = document.querySelectorAll('details[accordion="' + group + '"]')
      for (var i = 0; i < items.length; i++) {
        if (open) {
          if (items[i]._sprinkleOpen) items[i]._sprinkleOpen()
          else items[i].setAttribute('open', '')
        } else {
          if (items[i]._sprinkleClose) items[i]._sprinkleClose()
          else items[i].removeAttribute('open')
        }
      }
    })
  })

  /* ── 18. no-past ── */

  ForgeSprinkle.register('input[no-past]', function noPast(el) {
    if (el.type !== 'date' && el.type !== 'datetime-local') return
    var d = new Date()
    var y = d.getFullYear()
    var mo = String(d.getMonth() + 1).padStart(2, '0')
    var da = String(d.getDate()).padStart(2, '0')
    var today = y + '-' + mo + '-' + da
    if (el.type === 'datetime-local') {
      var h = String(d.getHours()).padStart(2, '0')
      var mi = String(d.getMinutes()).padStart(2, '0')
      el.setAttribute('min', today + 'T' + h + ':' + mi)
    } else {
      el.setAttribute('min', today)
    }
    el.addEventListener('change', function () {
      if (!el.value) return
      var sel = new Date(el.value + (el.type === 'date' ? 'T00:00:00' : ''))
      if (sel < new Date()) {
        el.value = ''
        el.setCustomValidity('Past dates are not allowed')
      } else {
        el.setCustomValidity('')
      }
    })
  })

  /* ── 19. no-future ── */

  ForgeSprinkle.register('input[no-future]', function noFuture(el) {
    if (el.type !== 'date' && el.type !== 'datetime-local') return
    var d = new Date()
    var y = d.getFullYear()
    var mo = String(d.getMonth() + 1).padStart(2, '0')
    var da = String(d.getDate()).padStart(2, '0')
    var today = y + '-' + mo + '-' + da
    if (el.type === 'datetime-local') {
      var h = String(d.getHours()).padStart(2, '0')
      var mi = String(d.getMinutes()).padStart(2, '0')
      el.setAttribute('max', today + 'T' + h + ':' + mi)
    } else {
      el.setAttribute('max', today)
    }
    el.addEventListener('change', function () {
      if (!el.value) return
      var sel = new Date(el.value + (el.type === 'date' ? 'T00:00:00' : ''))
      if (sel > new Date()) {
        el.value = ''
        el.setCustomValidity('Future dates are not allowed')
      } else {
        el.setCustomValidity('')
      }
    })
  })

  /* ── 20. disable-days ── */

  ForgeSprinkle.register('input[disable-days]', function disableDays(el) {
    if (el.type !== 'date' && el.type !== 'datetime-local') return
    var dayMap = { sun: 0, mon: 1, tue: 2, wed: 3, thu: 4, fri: 5, sat: 6 }
    function isDisabled(date) {
      var rule = el.getAttribute('disable-days')
      if (!rule) return false
      var day = date.getDay()
      if (rule === 'weekends') return day === 0 || day === 6
      if (rule === 'weekdays') return day >= 1 && day <= 5
      var parts = rule.split(',').map(function (s) { return s.trim().toLowerCase().slice(0, 3) })
      return parts.some(function (p) { return dayMap[p] === day })
    }
    el.addEventListener('change', function () {
      if (!el.value) return
      var d = new Date(el.value + (el.type === 'date' ? 'T00:00:00' : ''))
      if (isDisabled(d)) {
        el.value = ''
        el.setCustomValidity('This date is not available')
      } else {
        el.setCustomValidity('')
      }
    })
  })

  /* ── 21. business-hours ── */

  ForgeSprinkle.register('input[type="datetime-local"][business-hours]', function businessHours(el) {
    function parseTime(str) {
      var m = str.trim().match(/^(\d{1,2}):(\d{2})(?:\s*(AM|PM))?$/i)
      if (!m) return null
      var h = parseInt(m[1], 10)
      var min = parseInt(m[2], 10)
      if (m[3]) {
        var pm = m[3].toUpperCase() === 'PM'
        if (pm && h !== 12) h += 12
        if (!pm && h === 12) h = 0
      }
      return h * 60 + min
    }
    var range = el.getAttribute('business-hours')
    var parts = range ? range.split('-') : []
    if (parts.length < 2) return
    var startMin = parseTime(parts[0])
    var endMin = parseTime(parts[1])
    if (startMin === null || endMin === null) return
    el.addEventListener('change', function () {
      if (!el.value) return
      var t = el.value.split('T')[1]
      if (!t) return
      var hm = t.split(':')
      var mins = parseInt(hm[0], 10) * 60 + parseInt(hm[1], 10)
      if (mins < startMin || mins > endMin) {
        var closest = mins < startMin ? startMin : endMin
        var h = String(Math.floor(closest / 60)).padStart(2, '0')
        var m = String(closest % 60).padStart(2, '0')
        el.value = el.value.split('T')[0] + 'T' + h + ':' + m
        el.setCustomValidity('Time must be between ' + parts[0].trim() + ' and ' + parts[1].trim())
      } else {
        el.setCustomValidity('')
      }
    })
  })

  /* ── 22. date-range ── */

  ForgeSprinkle.register('input[date-range]', function dateRange(el) {
    var group = el.getAttribute('date-range')
    var role = el.getAttribute('data-range-type')
    if (!group || !role) return
    var q = 'input[date-range="' + group + '"][data-range-type="' + (role === 'start' ? 'end' : 'start') + '"]'
    var partner = document.querySelector(q)
    if (!partner) return
    var delta = null
    function dateOnly(v) { return (v || '').split('T')[0] }
    function calcDelta() {
      if (el.value && partner.value) {
        var d1 = new Date(dateOnly(el.value) + 'T00:00:00')
        var d2 = new Date(dateOnly(partner.value) + 'T00:00:00')
        delta = Math.round((d2 - d1) / 86400000)
      } else {
        delta = null
      }
    }
    function addDays(v, n) {
      var isDT = v.indexOf('T') !== -1
      var d = new Date(dateOnly(v) + 'T00:00:00')
      d.setDate(d.getDate() + n)
      var r = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
      return isDT ? r + 'T' + (v.split('T')[1] || '00:00') : r
    }
    if (el.value && partner.value) calcDelta()
    el.addEventListener('change', function () {
      if (role === 'start' && delta !== null && partner.value) {
        var adj = addDays(el.value, delta)
        var pMin = partner.getAttribute('min')
        var pMax = partner.getAttribute('max')
        if (pMin && adj.substring(0, 10) < pMin.substring(0, 10)) adj = pMin
        if (pMax && adj.substring(0, 10) > pMax.substring(0, 10)) adj = pMax
        partner.value = adj
      }
      if (el.value && partner.value) calcDelta()
      if (role === 'start' && el.value) {
        partner.focus()
        if (partner.showPicker) partner.showPicker()
      }
    })
    function onFocus() {
      el.setAttribute('data-sprinkle-range-active', '')
      partner.setAttribute('data-sprinkle-range-active', '')
    }
    function onBlur() {
      setTimeout(function () {
        if (document.activeElement !== partner && document.activeElement !== el) {
          el.removeAttribute('data-sprinkle-range-active')
          partner.removeAttribute('data-sprinkle-range-active')
        }
      }, 0)
    }
    el.addEventListener('focus', onFocus)
    el.addEventListener('blur', onBlur)
    partner.addEventListener('focus', onFocus)
    partner.addEventListener('blur', onBlur)
  })

  /* ── 23. date-input (visual standardization) ── */

  ForgeSprinkle.register('input[date-input]', function dateInput(el) {
    if (el.type !== 'date' && el.type !== 'datetime-local') return
  })

  /* ── 24. error-message ── */

  var ERR_STATE_ATTR = {
    valueMissing: 'required',
    typeMismatch: 'type',
    patternMismatch: 'pattern',
    tooShort: 'minlength',
    tooLong: 'maxlength',
    rangeUnderflow: 'min',
    rangeOverflow: 'max',
    stepMismatch: 'step',
    badInput: 'badinput'
  }

  var ERR_DEFAULTS = {
    valueMissing: 'This field is required.',
    tooShort: 'Value is too short.',
    tooLong: 'Value is too long.',
    rangeUnderflow: 'Value is too low.',
    rangeOverflow: 'Value is too high.',
    typeMismatch: 'Please enter a valid value.',
    patternMismatch: 'Please match the requested format.',
    stepMismatch: 'Please enter a valid step value.',
    badInput: 'Please enter a valid number.'
  }

  var errIdCounter = 0

  var errSelector = '[error-message],[allowed-domains]'
  for (var k in ERR_STATE_ATTR) {
    errSelector += ',[error-message-' + ERR_STATE_ATTR[k] + ']'
  }

  function getErrorMsg(el) {
    var val = el.validity
    var msg = el.getAttribute('error-message')
    for (var state in ERR_STATE_ATTR) {
      if (val[state]) {
        var custom = el.getAttribute('error-message-' + ERR_STATE_ATTR[state])
        if (custom) return custom
        if (msg) return msg
        return ERR_DEFAULTS[state] || el.validationMessage
      }
    }
    if (val.customError) {
      return el.validationMessage
    }
    if (el._sprinkleMaxLen !== undefined && el.value.length > el._sprinkleMaxLen) {
      var custom = el.getAttribute('error-message-maxlength')
      if (custom) return custom
      if (msg) return msg
      return ERR_DEFAULTS.tooLong || el.validationMessage
    }
    return msg || el.validationMessage
  }

  ForgeSprinkle.register(errSelector, function errorMessage(el) {
    if (el._sprinkleErrorInit) return
    el._sprinkleErrorInit = true

    var msgEl = document.createElement('div')
    msgEl.className = 'sprinkle-error'
    msgEl.setAttribute('role', 'alert')
    msgEl.setAttribute('aria-hidden', 'true')
    msgEl.id = 'sprinkle-err-' + (++errIdCounter)

    function getRef() {
      if (el._sprinkleErrorRef) return el._sprinkleErrorRef
      return el.parentNode && el.parentNode.classList.contains('sprinkle-enhance-wrap')
        ? el.parentNode
        : el
    }

    el._sprinkleErrorShow = function () {
      msgEl.textContent = getErrorMsg(el)
      el.setAttribute('aria-invalid', 'true')
      msgEl.setAttribute('aria-hidden', 'false')
      el.setAttribute('aria-describedby', msgEl.id)
      var r = getRef()
      r.parentNode.insertBefore(msgEl, r.nextSibling)
    }

    el._sprinkleErrorClear = function () {
      if (msgEl.parentNode) msgEl.parentNode.removeChild(msgEl)
      el.removeAttribute('aria-invalid')
      el.removeAttribute('aria-describedby')
      msgEl.setAttribute('aria-hidden', 'true')
    }

    var max = el.getAttribute('maxlength')
    if (max !== null) {
      el._sprinkleMaxLen = parseInt(max, 10)
      el.removeAttribute('maxlength')
    }

    var form = el.closest('form')
    if (form && !form._sprinkleErrorInit) {
      form._sprinkleErrorInit = true
      form.setAttribute('novalidate', '')
      form.addEventListener('submit', function (e) {
        e.preventDefault()
        for (var i = 0; i < form.elements.length; i++) {
          if (form.elements[i]._sprinkleErrorClear) form.elements[i]._sprinkleErrorClear()
        }
        var valid = true
        var first = null
        for (var i = 0; i < form.elements.length; i++) {
          var c = form.elements[i]
          if (!c._sprinkleErrorShow) continue
          var invalid = !c.checkValidity() ||
            (c._sprinkleMaxLen !== undefined && c.value.length > c._sprinkleMaxLen)
          if (invalid) {
            valid = false
            if (!first) first = c
            c._sprinkleErrorShow()
          }
        }
        if (valid) {
          if (form.hasAttribute('enhance')) return
          form.submit()
          return
        }
        if (first) first.focus()
      })
    }

    el.addEventListener('invalid', function (e) {
      e.preventDefault()
      el._sprinkleErrorShow()
    })

    el.addEventListener('input', function () {
      if (el._sprinkleMaxLen !== undefined && el.value.length > el._sprinkleMaxLen) {
        el._sprinkleErrorShow()
      } else {
        el._sprinkleErrorClear()
      }
    })
  })

  /* ── 25. otp ── */

  ForgeSprinkle.register('input[otp]', function otp(el) {
    if (el._sprinkleOtpInit) return
    var N = parseInt(el.getAttribute('max'), 10)
    if (isNaN(N) || N < 2) return
    el._sprinkleOtpInit = true

    el.type = 'text'
    el.inputMode = 'numeric'
    el.pattern = '[0-9]{' + N + '}'
    el.maxLength = N
    el.autocomplete = 'one-time-code'

    var container = document.createElement('div')
    container.className = 'sprinkle-otp'
    container.setAttribute('role', 'group')
    container.setAttribute('aria-label', 'One-time code, ' + N + ' digits')

    var boxes = []

    for (var i = 0; i < N; i++) {
      (function (idx) {
        var box = document.createElement('input')
        box.type = 'text'
        box.inputMode = 'numeric'
        box.maxLength = 1
        box.pattern = '[0-9]'
        box.className = 'sprinkle-otp-box'
        box.setAttribute('aria-label', 'Digit ' + (idx + 1) + ' of ' + N)
        box.setAttribute('autocomplete', 'one-time-code')

        box.addEventListener('input', function () {
          this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1)
          if (this.value && idx < N - 1) boxes[idx + 1].focus()
          syncValue()
        })

        box.addEventListener('keydown', function (e) {
          if (e.key === 'Backspace') {
            if (!this.value && idx > 0) {
              boxes[idx - 1].focus()
              boxes[idx - 1].value = ''
              syncValue()
            }
          } else if (e.key === 'ArrowLeft' && idx > 0) {
            e.preventDefault()
            boxes[idx - 1].focus()
          } else if (e.key === 'ArrowRight' && idx < N - 1) {
            e.preventDefault()
            boxes[idx + 1].focus()
          }
        })

        box.addEventListener('paste', function (e) {
          e.preventDefault()
          var data = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, N)
          for (var j = 0; j < data.length && j < N; j++) {
            boxes[j].value = data[j]
          }
          boxes[Math.min(data.length, N - 1)].focus()
          syncValue()
        })

        container.appendChild(box)
        boxes.push(box)
      })(i)
    }

    var ref = el.parentNode && el.parentNode.classList.contains('sprinkle-enhance-wrap')
      ? el.parentNode
      : el
    ref.parentNode.insertBefore(container, ref.nextSibling)

    el._sprinkleErrorRef = container

    el.addEventListener('focus', function () {
      for (var j = 0; j < N; j++) {
        if (!boxes[j].value) { boxes[j].focus(); return }
      }
      boxes[N - 1].focus()
    })

    function syncValue() {
      el.value = boxes.map(function (b) { return b.value }).join('')
    }

  })

  /* ── 26. prefix ── */

  ForgeSprinkle.register('input[type="url"][prefix]', function prefix(el) {
    var v = el.getAttribute('prefix') || ''
    var prefixText = 'https://' + v + (v ? '.' : '')

    el.addEventListener('input', function (e) {
      if (e.inputType && e.inputType.indexOf('delete') !== -1) return
      if (el.value && el.value.indexOf('://') === -1 && el.value.slice(0, prefixText.length) !== prefixText) {
        el.value = prefixText + el.value
        el.setSelectionRange(el.value.length, el.value.length)
      }
    })
  })

  /* ── 27. mask ── */

  function applyMask(raw, mask) {
    var digits = raw.replace(/[^0-9]/g, '')
    var result = ''
    var di = 0
    for (var mi = 0; mi < mask.length && di < digits.length; mi++) {
      result += mask[mi] === '0' ? digits[di++] : mask[mi]
    }
    return result
  }

  function defaultMask(el) {
    if (el.getAttribute('mask') !== null) {
      if (el.type === 'tel' && !el.getAttribute('mask')) return '(000) 000-0000'
    }
    return el.getAttribute('mask') || ''
  }

  ForgeSprinkle.register('input[type="tel"][mask], input[type="text"][mask]', function mask(el) {
    var maskStr = defaultMask(el)
    if (!maskStr) return

    el.addEventListener('input', function () {
      var caret = el.selectionStart
      var before = el.value.slice(0, caret)
      var digitsBefore = (before.match(/[0-9]/g) || []).length
      el.value = applyMask(el.value, maskStr)
      var newCaret = 0
      var di = 0
      for (var mi = 0; mi < maskStr.length && di < digitsBefore; mi++) {
        newCaret++
        if (maskStr[mi] === '0') di++
      }
      el.setSelectionRange(newCaret, newCaret)
    })
  })

  /* ── 28. allowed-domains ── */

  ForgeSprinkle.register('input[allowed-domains]', function allowedDomains(el) {
    function extractDomain(v) {
      if (el.type === 'email') {
        var at = v.indexOf('@')
        return at >= 0 ? v.slice(at + 1) : ''
      }
      try { return new URL(v).hostname } catch (e) { return '' }
    }

    function validate() {
      if (!el.value) { el.setCustomValidity(''); el.checkValidity(); return }
      var list = el.getAttribute('allowed-domains').split(',').map(function (s) { return s.trim() })
      var domain = extractDomain(el.value)
      if (domain && list.indexOf(domain) === -1) {
        el.setCustomValidity(el.getAttribute('error-message-allowed-domains') || 'Domain not allowed.')
      } else {
        el.setCustomValidity('')
      }
      el.checkValidity()
    }

    el.addEventListener('input', validate)
    el.addEventListener('change', validate)
  })

  /* ── 29. avatar (CSS-only, empty handler for observer compat) ── */

  ForgeSprinkle.register('img[avatar]', function avatar(el) {})

  /* ── 30. tooltip ── */

  ForgeSprinkle.register('[tooltip]', function tooltip(el) {
    if (el.hasAttribute('title')) el.removeAttribute('title')

    function position() {
      var rect = el.getBoundingClientRect()
      var spaceAbove = rect.top
      var spaceBelow = window.innerHeight - rect.bottom
      var spaceLeft = rect.left
      var spaceRight = window.innerWidth - rect.right

      var text = el.getAttribute('tooltip') || ''
      var estW = Math.min(text.length * 7 + 16, 360)
      var estH = 28

      el.classList.remove('sprinkle-tip-bottom', 'sprinkle-tip-left', 'sprinkle-tip-right')

      if (spaceAbove >= estH) return
      if (spaceBelow >= estH) { el.classList.add('sprinkle-tip-bottom'); return }
      if (spaceRight >= estW) { el.classList.add('sprinkle-tip-right'); return }
      if (spaceLeft >= estW) { el.classList.add('sprinkle-tip-left'); return }
      el.classList.add('sprinkle-tip-bottom')
    }

    el.addEventListener('mouseenter', position)
    el.addEventListener('focusin', position)
  })

  /* ── 31. breadcrumb ── */

  ForgeSprinkle.register('ul[breadcrumb]', function breadcrumb(el) {})

  /* ── 32. drawer ── */

  ForgeSprinkle.register('dialog[drawer]', function drawer(el) {
    el.addEventListener('click', function (e) {
      var btn = e.target.closest('[command-for="' + el.id + '"], [commandfor="' + el.id + '"]')
      if (btn && btn.getAttribute('command') === 'close') {
        e.preventDefault()
        el.close()
      }
    })
  })

  /* ── 33. modal ── */

  ForgeSprinkle.register('dialog[modal]', function modal(el) {
    el.addEventListener('click', function (e) {
      var btn = e.target.closest('[command-for="' + el.id + '"], [commandfor="' + el.id + '"]')
      if (btn && btn.getAttribute('command') === 'close') {
        e.preventDefault()
        el.close()
      }
    })
  })

  /* ── 34. dropdown ── */

  ForgeSprinkle.register('details[dropdown]', function dropdown(el) {
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && el.open) {
        el.removeAttribute('open')
        el.querySelector('summary').focus()
        e.preventDefault()
        return
      }
      if (!el.open) return
      var items = el.querySelectorAll('a, button, [role="menuitem"], li > a, li > button')
      var focusable = Array.from(items).filter(function (item) {
        return !item.disabled && item.offsetParent !== null
      })
      if (!focusable.length) return
      var idx = focusable.indexOf(document.activeElement)
      if (e.key === 'ArrowDown') {
        e.preventDefault()
        focusable[(idx + 1) % focusable.length].focus()
      } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        focusable[(idx - 1 + focusable.length) % focusable.length].focus()
      } else if (e.key === 'Home') {
        e.preventDefault()
        focusable[0].focus()
      } else if (e.key === 'End') {
        e.preventDefault()
        focusable[focusable.length - 1].focus()
      }
    })
  })

  /* ── 35. shell ── */

  ForgeSprinkle.register('[shell]', function shell(el) {
    var left = el.querySelector(':scope > [sidebar="left"]')
    var right = el.querySelector(':scope > [sidebar="right"]')
    var top = el.querySelector(':scope > [sidebar="top"]')
    var bottom = el.querySelector(':scope > [sidebar="bottom"]')
    var content = el.querySelector(':scope > [content]')

    var cols = []
    if (left) cols.push('auto')
    cols.push('1fr')
    if (right) cols.push('auto')

    var rows = []
    if (top) rows.push('auto')
    rows.push('1fr')
    if (bottom) rows.push('auto')

    el.style.gridTemplateColumns = cols.join(' ')
    el.style.gridTemplateRows = rows.join(' ')

    var tc = cols.length
    var tr = rows.length
    var cc = left ? 2 : 1
    var cr = top ? 2 : 1

    if (left) {
      left.style.gridColumn = '1'
      left.style.gridRow = '1 / ' + (tr + 1)
    }
    if (right) {
      right.style.gridColumn = '' + tc
      right.style.gridRow = '1 / ' + (tr + 1)
    }
    if (top) {
      top.style.gridColumn = '1 / ' + (tc + 1)
      top.style.gridRow = '1'
    }
    if (bottom) {
      bottom.style.gridColumn = '1 / ' + (tc + 1)
      bottom.style.gridRow = '' + tr
    }
    if (content) {
      content.style.gridColumn = '' + cc
      content.style.gridRow = '' + cr
    }
  })

  /* ── 36. theme-toggle ── */

  function applyTheme(dark) {
    document.documentElement.setAttribute('data-sprinkle-theme', dark ? 'dark' : 'light')
    try { localStorage.setItem('sprinkle-theme', dark ? 'dark' : 'light') } catch (e) {}
  }

  function initTheme() {
    var saved
    try { saved = localStorage.getItem('sprinkle-theme') } catch (e) {}
    if (saved === 'dark') applyTheme(true)
    else if (saved === 'light') applyTheme(false)
    else applyTheme(window.matchMedia('(prefers-color-scheme: dark)').matches)
  }

  function isDarkTheme() {
    var attr = document.documentElement.getAttribute('data-sprinkle-theme')
    if (attr === 'dark') return true
    if (attr === 'light') return false
    return window.matchMedia('(prefers-color-scheme: dark)').matches
  }

  ForgeSprinkle.register('[theme-toggle]', function themeToggle(el) {
    el.setAttribute('aria-label', isDarkTheme() ? 'Switch to light theme' : 'Switch to dark theme')
    el.addEventListener('click', function () {
      applyTheme(!isDarkTheme())
      el.setAttribute('aria-label', isDarkTheme() ? 'Switch to light theme' : 'Switch to dark theme')
    })
  })

  /* ── 37. nav ── */

  ForgeSprinkle.register('ul[nav]', function nav(el) {
    el.addEventListener('toggle', function (e) {
      if (e.target.tagName !== 'DETAILS' || !e.target.hasAttribute('nav-group')) return
      if (!e.target.open) return
      var group = e.target.getAttribute('nav-group') || ''
      var open = el.querySelectorAll('details[nav-group' + (group ? '="' + group + '"' : '') + '][open]')
      for (var i = 0; i < open.length; i++) {
        if (open[i] !== e.target) open[i].removeAttribute('open')
      }
    }, true)
  })

  /* ── 38. form enhance ── */

  ForgeSprinkle.register('form[enhance]', function enhanceForm(form) {
    if (form._sprinkleEnhanceInit) return
    form._sprinkleEnhanceInit = true

    form.setAttribute('novalidate', '')

    var selector = 'input:not([type="submit"]):not([type="reset"]):not([type="button"]):not([type="hidden"]), textarea, select'
    form.querySelectorAll(selector).forEach(function (el) {
      if (!el.hasAttribute('error-message')) {
        el.setAttribute('error-message', '')
        matchNode(el)
      }
    })

    var msgEl = document.createElement('div')
    msgEl.className = 'sprinkle-form-msg'
    msgEl.style.display = 'none'
    form.insertBefore(msgEl, form.firstChild)

    function showMsg(type, text) {
      msgEl.className = 'sprinkle-form-msg sprinkle-form-msg-' + type
      msgEl.textContent = text
      msgEl.style.display = ''
    }

    function clearMsg() {
      msgEl.style.display = 'none'
      msgEl.textContent = ''
    }

    function clearServerError() {
      if (this._sprinkleServerError) {
        this._sprinkleServerError = null
        this.setCustomValidity('')
        if (this._sprinkleErrorClear) this._sprinkleErrorClear()
      }
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault()
      clearMsg()

      if (!form.checkValidity()) return

      var btns = form.querySelectorAll('button[type="submit"], input[type="submit"]')
      btns.forEach(function (btn) {
        btn.classList.add('sprinkle-loading')
        btn.disabled = true
        btn.setAttribute('aria-busy', 'true')
      })

      var url = form.getAttribute('action') || window.location.href
      var method = (form.getAttribute('method') || 'GET').toUpperCase()

      fetch(url, {
        method: method,
        body: new FormData(form),
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      })
      .then(function (res) {
        return res.json().then(function (data) {
          return { status: res.status, ok: res.ok, data: data }
        }).catch(function () {
          return { status: res.status, ok: res.ok, data: null }
        })
      })
      .then(function (result) {
        var data = result.data

        if (data && data.errors) {
          for (var name in data.errors) {
            var el = form.elements[name]
            if (!el) continue
            el._sprinkleServerError = data.errors[name]
            el.setCustomValidity(data.errors[name])
            if (el._sprinkleErrorShow) el._sprinkleErrorShow()
            el.addEventListener('input', clearServerError)
          }
        }

        if (result.ok && data && data.success !== false) {
          showMsg('success', data.message || 'Form submitted successfully.')
          form.reset()
        } else {
          showMsg('error', data && data.message ? data.message : 'An error occurred. Please try again.')
        }
      })
      .catch(function () {
        showMsg('error', 'A network error occurred. Please check your connection and try again.')
      })
      .finally(function () {
        btns.forEach(function (btn) {
          btn.classList.remove('sprinkle-loading')
          btn.disabled = false
          btn.removeAttribute('aria-busy')
        })
      })
    })
  })

  /* ── 39. boost ── */

  var boostCache = {}
  var boostUrl = window.location.href.split('#')[0]

  function processSubtree(root) {
    if (hasSprinkleAttr(root)) matchNode(root)
    var all = root.querySelectorAll('*')
    for (var i = 0; i < all.length; i++) {
      if (hasSprinkleAttr(all[i])) matchNode(all[i])
    }
  }

  function boostNavigate(url, opts) {
    opts = opts || {}
    url = url.split('#')[0]
    if (url === boostUrl) return
    boostUrl = url

    document.body.classList.add('sprinkle-boost-loading')

    var cached = boostCache[url]
    delete boostCache[url]

    var promise = cached || fetch(url, { cache: 'no-store' }).then(function (r) {
      if (!r.ok) throw new Error('fetch failed')
      return r.text()
    })

    return promise.then(function (html) {
      if (!html || !html.trim()) {
        document.location.href = url
        return
      }

      var m = html.match(/<title[^>]*>([\s\S]*?)<\/title>/i)
      if (m) document.title = m[1].trim()

      var doc = new DOMParser().parseFromString(html, 'text/html')
      var target = document.querySelector('[boost-container]') || document.querySelector('main') || document.body
      var src = doc.querySelector('[boost-container]') || doc.querySelector('main') || doc.body

      if (!src) { document.location.href = url; return }

      target.innerHTML = src.innerHTML
      processSubtree(target)

      if (!opts.replace) history.pushState(null, '', url)
      window.scrollTo({ top: 0, left: 0 })

      var h = target.querySelector('h1, h2')
      if (h) { h.setAttribute('tabindex', '-1'); h.focus({ preventScroll: true }) }
    })
    .catch(function (err) {
      boostUrl = null
      console.error('[boost]', err && err.message ? err.message : err)
      document.location.href = url
    })
    .finally(function () {
      document.body.classList.remove('sprinkle-boost-loading')
    })
  }

  ForgeSprinkle.register('[boost]', function boostLink(el) {
    if (el.tagName === 'A') {
      var href = el.getAttribute('href')
      if (!href || href === '#' || href.indexOf('javascript:') === 0) return
      if (href.indexOf('://') > 0 && href.indexOf(location.origin) !== 0) return

      if (el.getAttribute('boost') === 'hover') {
        el.addEventListener('mouseenter', function () {
          if (!boostCache[href]) boostCache[href] = fetch(href, { cache: 'no-store' }).then(function (r) { return r.text() })
        }, { once: true })
        el.addEventListener('touchstart', function () {
          if (!boostCache[href]) boostCache[href] = fetch(href, { cache: 'no-store' }).then(function (r) { return r.text() })
        }, { once: true })
      }

      el.addEventListener('click', function (e) {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button) return
        e.preventDefault()
        boostNavigate(href)
      })
    } else {
      if (el.getAttribute('boost') === 'hover') {
        function prefetch(e) {
          var a = e.target.closest('a')
          if (!a || a._boostPrefetched) return
          var href = a.getAttribute('href')
          if (!href || href === '#' || href.indexOf('javascript:') === 0) return
          if (href.indexOf('://') > 0 && href.indexOf(location.origin) !== 0) return
          a._boostPrefetched = true
          if (!boostCache[href]) boostCache[href] = fetch(href, { cache: 'no-store' }).then(function (r) { return r.text() })
        }
        el.addEventListener('mouseover', prefetch, true)
        el.addEventListener('touchstart', prefetch, true)
      }

      el.addEventListener('click', function (e) {
        var a = e.target.closest('a')
        if (!a) return
        var href = a.getAttribute('href')
        if (!href || href === '#' || href.indexOf('javascript:') === 0) return
        if (href.indexOf('://') > 0 && href.indexOf(location.origin) !== 0) return
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button) return
        e.preventDefault()
        boostNavigate(href)
      })
    }
  })

  window.addEventListener('popstate', function () {
    boostNavigate(location.href, { replace: true })
  })

  /* ── commandfor polyfill ── */

  function initCommandFor() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[commandfor], [command-for]')
      if (!btn) return
      var id = btn.getAttribute('commandfor') || btn.getAttribute('command-for')
      var cmd = btn.getAttribute('command')
      if (!id || !cmd) return
      var target = document.getElementById(id)
      if (!target) return
      if (target.tagName !== 'DIALOG') return
      e.preventDefault()
      if (cmd === 'show-modal' || cmd === 'showmodal') target.showModal()
      else if (cmd === 'close') target.close()
    })
  }

  /* ── boot ── */

  function init() {
    initTheme()
    var sel = directives.reduce(function (acc, d) {
      return acc + (acc ? ',' : '') + d[0]
    }, '')
    if (sel) document.querySelectorAll(sel).forEach(matchNode)
    initCommandFor()
    observe()
    initialized = true
  }

  var observed = false

  function observe() {
    if (observed) return
    observed = true
    var O = window.MutationObserver
    if (!O) return
    new O(function (mutations) {
      mutations.forEach(function (m) {
        m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return
          if (hasSprinkleAttr(n)) matchNode(n)
        })
      })
    }).observe(document.body, { childList: true, subtree: true })
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()
