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

  function pad(n) { return String(n).padStart(2, '0') }
  function fmtDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) }

  var CSS_SR_ONLY = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0'
  var CSS_DP_TRIGGER = 'font:inherit;padding:0.35em 2.2em 0.35em 0.5em;border:1px solid var(--sprinkle-dp-trigger-border);border-radius:4px;background:var(--sprinkle-dp-trigger-bg);color:var(--sprinkle-dp-trigger-color);min-width:14ch;box-sizing:border-box;cursor:pointer;text-align:left;position:relative'

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

  /* ── 11. drop-zone ── */

  ForgeSprinkle.register('label[drop-zone]', function dropZone(el) {
    if (el._sprinkleDropZone) return
    el._sprinkleDropZone = true

    var input = el.querySelector('input[type="file"]')
    if (!input) return

    var isMultiple = input.multiple
    var doc = el.ownerDocument

    var preview = el.querySelector('.sprinkle-drop-preview')
    if (!preview) {
      preview = doc.createElement('div')
      preview.className = 'sprinkle-drop-preview'
      preview.setAttribute('aria-live', 'polite')
      el.appendChild(preview)
    }

    var currentFiles = Array.from(input.files)
    var objectUrls = []

    function revokeUrls() {
      for (var i = 0; i < objectUrls.length; i++) URL.revokeObjectURL(objectUrls[i])
      objectUrls = []
    }

    function syncInput() {
      var dt = new DataTransfer()
      for (var i = 0; i < currentFiles.length; i++) dt.items.add(currentFiles[i])
      input.files = dt.files
    }

    function formatSize(bytes) {
      if (bytes < 1024) return bytes + ' B'
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
      return (bytes / 1048576).toFixed(1) + ' MB'
    }

    var dragCount = 0

    el.addEventListener('dragenter', function (e) {
      e.preventDefault()
      e.stopPropagation()
      dragCount++
      el.classList.add('sprinkle-drop-active')
    })

    el.addEventListener('dragover', function (e) {
      e.preventDefault()
      e.stopPropagation()
    })

    el.addEventListener('dragleave', function (e) {
      e.preventDefault()
      e.stopPropagation()
      dragCount--
      if (dragCount <= 0) {
        dragCount = 0
        el.classList.remove('sprinkle-drop-active')
      }
    })

    el.addEventListener('drop', function (e) {
      e.preventDefault()
      e.stopPropagation()
      dragCount = 0
      el.classList.remove('sprinkle-drop-active')

      var files = e.dataTransfer.files
      if (!files.length) return

      if (isMultiple) {
        for (var i = 0; i < files.length; i++) currentFiles.push(files[i])
      } else {
        currentFiles = [files[0]]
      }

      syncInput()
      input.dispatchEvent(new Event('change', { bubbles: true }))
      renderPreviews()
    })

    input.addEventListener('change', function () {
      currentFiles = Array.from(input.files)
      renderPreviews()
    })

    function renderPreviews() {
      revokeUrls()
      preview.innerHTML = ''
      if (!currentFiles.length) return

      for (var i = 0; i < currentFiles.length; i++) {
        ;(function (file, idx) {
          var item = doc.createElement('div')
          item.className = 'sprinkle-drop-file'

          if (file.type.startsWith('image/')) {
            var img = doc.createElement('img')
            img.src = URL.createObjectURL(file)
            objectUrls.push(img.src)
            img.alt = file.name
            item.appendChild(img)
          }

          var info = doc.createElement('div')
          info.className = 'sprinkle-drop-info'

          var name = doc.createElement('span')
          name.className = 'sprinkle-drop-name'
          name.textContent = file.name
          info.appendChild(name)

          var size = doc.createElement('span')
          size.className = 'sprinkle-drop-size'
          size.textContent = formatSize(file.size)
          info.appendChild(size)

          item.appendChild(info)

          var removeBtn = doc.createElement('button')
          removeBtn.type = 'button'
          removeBtn.className = 'sprinkle-drop-remove'
          removeBtn.setAttribute('aria-label', 'Remove ' + file.name)
          removeBtn.innerHTML = '&times;'
          removeBtn.addEventListener('click', function (e) {
            e.stopPropagation()
            currentFiles.splice(idx, 1)
            syncInput()
            renderPreviews()
          })
          item.appendChild(removeBtn)

          preview.appendChild(item)
        })(currentFiles[i], i)
      }
    }
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
      var target = e.target
      while (target) {
        if (target !== el && target.open && (target.tagName === 'DIALOG' || target.tagName === 'DETAILS')) return
        target = target.parentElement
      }
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

  /* ── 18/19. no-past / no-future ── */

  ForgeSprinkle.register('input[no-past], input[no-future]', function noPastFuture(el) {
    if (el.type !== 'date' && el.type !== 'datetime-local') return
    if (el._sprinkleDateInputInit) return
    var isPast = el.hasAttribute('no-past')
    var d = new Date()
    var today = fmtDate(d)
    if (el.type === 'datetime-local') {
      el.setAttribute(isPast ? 'min' : 'max', today + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()))
    } else {
      el.setAttribute(isPast ? 'min' : 'max', today)
    }
    el.addEventListener('change', function () {
      if (!el.value) return
      var sel = new Date(el.value + (el.type === 'date' ? 'T00:00:00' : ''))
      var cmp = isPast ? sel < new Date() : sel > new Date()
      if (cmp) {
        el.value = ''
        el.setCustomValidity(isPast ? 'Past dates are not allowed' : 'Future dates are not allowed')
      } else {
        el.setCustomValidity('')
      }
    })
  })

  /* ── 20. disable-days ── */

  ForgeSprinkle.register('input[disable-days]', function disableDays(el) {
    if (el.type !== 'date' && el.type !== 'datetime-local') return
    if (el._sprinkleDateInputInit) return
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
    if (el._sprinkleDateInputInit) return
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
    if (el._sprinkleDateInputInit) return
    if (el.hasAttribute('date-input')) return
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
      var r = fmtDate(d)
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

  /* ── 23. date-input (custom date picker) ── */

  ForgeSprinkle.register('input[date-input]', function dateInput(el) {
    if (el.type !== 'date' && el.type !== 'datetime-local') return
    if (el._sprinkleDateInputInit) return
    el._sprinkleDateInputInit = true

    var MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December']
    var MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun',
                        'Jul','Aug','Sep','Oct','Nov','Dec']
    var WEEKDAYS = ['Su','Mo','Tu','We','Th','Fr','Sa']
    var dayMap = { sun: 0, mon: 1, tue: 2, wed: 3, thu: 4, fri: 5, sat: 6 }
    var doc = el.ownerDocument || document

    /* ── helpers ── */

    function parseDate(v) {
      if (!v) return null
      var p = (v.split('T')[0]).split('-')
      if (p.length < 3) return null
      var d = new Date(+p[0], +p[1] - 1, +p[2])
      return (d.getFullYear() === +p[0] && d.getMonth() === +p[1] - 1 && d.getDate() === +p[2]) ? d : null
    }

    function fmtDisplay(d) {
      return d ? MONTHS[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() : ''
    }

    function sameDay(a, b) {
      return a && b && a.getFullYear() === b.getFullYear() &&
             a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
    }

    function startOfMonth(d) { return new Date(d.getFullYear(), d.getMonth(), 1) }

    function addMonths(d, n) {
      var r = new Date(d.getTime())
      var day = r.getDate()
      r.setMonth(r.getMonth() + n)
      if (r.getDate() !== day) r.setDate(0)
      return r
    }

    function daysIn(y, m) { return new Date(y, m + 1, 0).getDate() }
    function firstDow(y, m) { return new Date(y, m, 1).getDay() }

    /* ── state ── */

    var selectedDate = parseDate(el.value)
    var viewDate = selectedDate ? startOfMonth(selectedDate) : startOfMonth(new Date())

    var minDate = parseDate(el.getAttribute('min'))
    var maxDate = parseDate(el.getAttribute('max'))
    if (el.hasAttribute('no-past')) {
      var tp = new Date(); tp.setHours(0, 0, 0, 0)
      if (!minDate || tp > minDate) minDate = tp
    }
    if (el.hasAttribute('no-future')) {
      var tf = new Date(); tf.setHours(0, 0, 0, 0)
      if (!maxDate || tf < maxDate) maxDate = tf
    }
    var disableDaysAttr = el.getAttribute('disable-days')

    function isDOWDisabled(dow) {
      if (!disableDaysAttr) return false
      if (disableDaysAttr === 'weekends') return dow === 0 || dow === 6
      if (disableDaysAttr === 'weekdays') return dow >= 1 && dow <= 5
      var parts = disableDaysAttr.split(',')
      for (var i = 0; i < parts.length; i++) {
        if (dayMap[parts[i].trim().toLowerCase().slice(0, 3)] === dow) return true
      }
      return false
    }

    function isDateDisabled(d) {
      if (isDOWDisabled(d.getDay())) return true
      if (minDate && d < minDate) return true
      if (maxDate && d > maxDate) return true
      return false
    }

    /* ── range mode ── */

    var rangeGroup = el.getAttribute('date-range')
    var rangeRole = el.getAttribute('data-range-type')
    var isRange = !!(rangeGroup && rangeRole)
    var rangePartner = null

    /* ── build trigger ── */

    function buildTrigger() {
      var label = el.getAttribute('aria-label') || ''
      if (!label && el.id) {
        var l = doc.querySelector('label[for="' + el.id + '"]')
        if (l) label = l.textContent.trim()
      }
      if (!label) label = el.getAttribute('name') || 'Choose date'

      var t = doc.createElement('button')
      t.type = 'button'
      t.className = 'sprinkle-dp-trigger'
      t.style.cssText = CSS_DP_TRIGGER
      t.setAttribute('aria-label', label)
      t.setAttribute('aria-haspopup', 'dialog')
      t.setAttribute('aria-expanded', 'false')
      if (el.hasAttribute('required')) t.setAttribute('required', '')

      var icon = doc.createElement('span')
      icon.className = 'sprinkle-dp-icon'
      icon.setAttribute('aria-hidden', 'true')

      t._updateLabel = function (txt) {
        t.textContent = ''
        t.appendChild(doc.createTextNode(txt || label))
        t.appendChild(icon)
      }

      if (el.parentNode) el.parentNode.insertBefore(t, el.nextSibling)
      return t
    }

    el.style.cssText = CSS_SR_ONLY

    if (isRange) {
      rangePartner = document.querySelector(
        'input[date-range="' + rangeGroup + '"][data-range-type="' + (rangeRole === 'start' ? 'end' : 'start') + '"]'
      )

      if (el._sprinkleDpShared) {
        var trigger = buildTrigger()
        var sharedPopup = el._sprinkleDpShared
        el._sprinkleDpTrigger = trigger
        el._sprinkleDpUpdateTrigger = function () {
          var d = parseDate(el.value)
          trigger._updateLabel(d ? fmtDisplay(d) : '')
        }

        function toggleShared() {
          if (sharedPopup.getAttribute('aria-hidden') === 'true') sharedPopup._sprinkleDpOpen()
          else sharedPopup._sprinkleDpClose()
        }
        trigger.addEventListener('click', toggleShared)
        trigger.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleShared() }
          else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); sharedPopup._sprinkleDpOpen() }
        })

        el._sprinkleDpUpdateTrigger()
        return
      }
    }

    var trigger = buildTrigger()

    function updateTrigger() {
      trigger._updateLabel(selectedDate ? fmtDisplay(selectedDate) : '')
    }
    updateTrigger()

    /* ── build calendar panel ── */

    function buildPanel(initialDate, onSelect) {
      var panel = doc.createElement('div')
      panel.className = 'sprinkle-dp'

      var header = doc.createElement('div')
      header.className = 'sprinkle-dp-header'

      var prevBtn = doc.createElement('button')
      prevBtn.type = 'button'
      prevBtn.className = 'sprinkle-dp-prev'
      prevBtn.setAttribute('aria-label', 'Previous month')
      prevBtn.innerHTML = '&#8249;'

      var monthBtn = doc.createElement('button')
      monthBtn.type = 'button'
      monthBtn.className = 'sprinkle-dp-month'
      monthBtn.setAttribute('aria-live', 'polite')

      var nextBtn = doc.createElement('button')
      nextBtn.type = 'button'
      nextBtn.className = 'sprinkle-dp-next'
      nextBtn.setAttribute('aria-label', 'Next month')
      nextBtn.innerHTML = '&#8250;'

      header.appendChild(prevBtn)
      header.appendChild(monthBtn)
      header.appendChild(nextBtn)
      panel.appendChild(header)

      var weekdays = doc.createElement('div')
      weekdays.className = 'sprinkle-dp-weekdays'
      weekdays.setAttribute('role', 'row')
      for (var w = 0; w < 7; w++) {
        var sp = doc.createElement('span')
        sp.setAttribute('role', 'columnheader')
        sp.setAttribute('aria-label', MONTHS[w] || '')
        sp.textContent = WEEKDAYS[w]
        weekdays.appendChild(sp)
      }
      panel.appendChild(weekdays)

      var grid = doc.createElement('div')
      grid.className = 'sprinkle-dp-grid'
      grid.setAttribute('role', 'grid')
      panel.appendChild(grid)

      var clearBtn = doc.createElement('button')
      clearBtn.type = 'button'
      clearBtn.className = 'sprinkle-dp-clear'
      clearBtn.textContent = 'Clear'
      panel.appendChild(clearBtn)

      var gd = {
        panel: panel, header: header, prevBtn: prevBtn, monthBtn: monthBtn,
        nextBtn: nextBtn, grid: grid, clearBtn: clearBtn, weekdays: weekdays,
        viewDate: startOfMonth(initialDate),
        showMonths: false,
        onSelect: onSelect || null
      }

      prevBtn.addEventListener('click', function () {
        if (gd.showMonths) {
          gd.viewDate = new Date(gd.viewDate.getFullYear() - 1, gd.viewDate.getMonth(), 1)
        } else {
          gd.viewDate = addMonths(gd.viewDate, -1)
        }
        renderPanel(gd)
      })

      nextBtn.addEventListener('click', function () {
        if (gd.showMonths) {
          gd.viewDate = new Date(gd.viewDate.getFullYear() + 1, gd.viewDate.getMonth(), 1)
        } else {
          gd.viewDate = addMonths(gd.viewDate, 1)
        }
        renderPanel(gd)
      })

      monthBtn.addEventListener('click', function () {
        gd.showMonths = !gd.showMonths
        renderPanel(gd)
      })

      clearBtn.addEventListener('click', function () { (gd.onSelect || selectDate)(null) })

      return gd
    }

    /* ── render calendar ── */

    function renderPanel(gd) {
      var y = gd.viewDate.getFullYear()
      var m = gd.viewDate.getMonth()

      gd.grid.innerHTML = ''

      if (gd.showMonths) {
        gd.monthBtn.textContent = '' + y
        gd.weekdays.style.display = 'none'
        gd.grid.setAttribute('aria-label', 'Months in ' + y)
        gd.grid.style.display = ''
        gd.grid.style.gridTemplateColumns = 'repeat(3, 1fr)'

        for (var i = 0; i < 12; i++) {
          ;(function(idx) {
            var btn = doc.createElement('button')
            btn.type = 'button'
            btn.textContent = MONTHS_SHORT[idx]
            btn.setAttribute('aria-label', MONTHS[idx] + ' ' + y)
            btn.style.cssText = 'width:auto;font:inherit;border:none;background:none;' +
              'cursor:pointer;border-radius:4px;color:var(--sprinkle-dp-text);font-size:0.85em'
            if (idx === m) {
              btn.style.background = 'var(--sprinkle-dp-accent)'
              btn.style.color = 'var(--sprinkle-dp-accent-text)'
              btn.style.fontWeight = '600'
            }
            var ms = new Date(y, idx, 1)
            var me = new Date(y, idx + 1, 0)
            var allDis = true
            for (var d = 1; d <= me.getDate(); d++) {
              if (!isDateDisabled(new Date(y, idx, d))) { allDis = false; break }
            }
            if (allDis) { btn.style.opacity = '0.4'; btn.style.cursor = 'default' }
            btn.addEventListener('click', function () {
              if (allDis) return
              gd.viewDate = new Date(y, idx, 1)
              gd.showMonths = false
              renderPanel(gd)
            })
            gd.grid.appendChild(btn)
          })(i)
        }
        gd.clearBtn.style.display = ''
        return
      }

      gd.monthBtn.textContent = MONTHS[m] + ' ' + y
      gd.weekdays.style.display = ''
      gd.grid.style.display = ''
      gd.grid.style.gridTemplateColumns = ''
      gd.grid.setAttribute('aria-label', MONTHS[m] + ' ' + y)

      var startDow = firstDow(y, m)
      var totalDays = daysIn(y, m)
      var today = new Date(); today.setHours(0, 0, 0, 0)

      for (var p = 0; p < startDow; p++) {
        var emp = doc.createElement('span')
        emp.className = 'sprinkle-dp-empty'
        emp.setAttribute('aria-hidden', 'true')
        gd.grid.appendChild(emp)
      }

      for (var d = 1; d <= totalDays; d++) {
        var cellDate = new Date(y, m, d)
        var btn = doc.createElement('button')
        btn.type = 'button'
        btn.className = 'sprinkle-dp-day'
        btn.setAttribute('role', 'gridcell')
        btn.setAttribute('tabindex', '-1')
        btn.setAttribute('data-date', fmtDate(cellDate))
        btn.setAttribute('aria-label', fmtDisplay(cellDate))
        btn.textContent = d

        if (isDateDisabled(cellDate)) {
          btn.setAttribute('aria-disabled', 'true')
        }
        if (sameDay(cellDate, today)) btn.classList.add('sprinkle-dp-today')
        if (sameDay(cellDate, selectedDate)) {
          btn.classList.add('sprinkle-dp-selected')
          btn.setAttribute('aria-selected', 'true')
          btn.setAttribute('tabindex', '0')
        }

        if (isRange && selectedDate) {
          var rangeS = rangeRole === 'start' ? selectedDate : parseDate(rangePartner ? rangePartner.value : '')
          var rangeE = rangeRole === 'start' ? parseDate(rangePartner ? rangePartner.value : '') : selectedDate
          if (rangeS && rangeE) {
            if (rangeS > rangeE) { var tmp = rangeS; rangeS = rangeE; rangeE = tmp }
            if (sameDay(cellDate, rangeS)) btn.classList.add('sprinkle-dp-range-start')
            else if (sameDay(cellDate, rangeE)) btn.classList.add('sprinkle-dp-range-end')
            else if (cellDate > rangeS && cellDate < rangeE) btn.classList.add('sprinkle-dp-range-between')
          }
        }

        if (!isDateDisabled(cellDate)) {
          btn.addEventListener('click', (function (dt) {
            return function () { (gd.onSelect || selectDate)(dt) }
          })(cellDate))
        }

        gd.grid.appendChild(btn)
      }

      var totalCells = startDow + totalDays
      var rem = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7)
      var padded = totalCells + rem
      var padTo = 42
      var extra = padTo - padded
      for (var n = 0; n < rem + extra; n++) {
        var emp2 = doc.createElement('span')
        emp2.className = 'sprinkle-dp-empty'
        emp2.setAttribute('aria-hidden', 'true')
        gd.grid.appendChild(emp2)
      }

      gd.clearBtn.style.display = ''
    }

    /* ── select date ── */

    function selectDate(d, targetEl) {
      if (d && isDateDisabled(d)) return

      targetEl = targetEl || el

      if (isRange) {
        if (targetEl === el) {
          selectedDate = d
          if (d && rangePartner) {
            var endVal = rangePartner.value
            var endDate = parseDate(endVal)
            if (endDate && endDate < d) {
              rangePartner.value = fmtDate(d)
              rangePartner.dispatchEvent(new Event('change', { bubbles: true }))
            }
          }
        } else {
          if (d && rangePartner) {
            var startVal = el.value
            var startDate = parseDate(startVal)
            if (startDate && d < startDate) return
          }
          targetEl.value = d ? fmtDate(d) : ''
          targetEl.dispatchEvent(new Event('input', { bubbles: true }))
          targetEl.dispatchEvent(new Event('change', { bubbles: true }))
        }
        renderPanel(grids[0])
        renderPanel(grids[1])
      } else {
        selectedDate = d
        viewDate = startOfMonth(d || new Date())
        grids[0].viewDate = viewDate
        renderPanel(grids[0])
        close()
      }

      updateTrigger()
      syncHiddenInput()
      el.dispatchEvent(new Event('input', { bubbles: true }))
      el.dispatchEvent(new Event('change', { bubbles: true }))

      if (isRange && rangePartner && rangePartner._sprinkleDpUpdateTrigger) {
        rangePartner._sprinkleDpUpdateTrigger()
      }
    }

    function syncHiddenInput() {
      el.value = selectedDate ? fmtDate(selectedDate) : ''
    }

    /* ── build panels & popup ── */

    var grids = []
    var popup

    if (isRange) {
      popup = doc.createElement('div')
      popup.className = 'sprinkle-dp-range-wrap'
      popup.setAttribute('role', 'dialog')
      popup.setAttribute('aria-modal', 'true')
      popup.setAttribute('aria-label', 'Choose date range')
      popup.setAttribute('aria-hidden', 'true')

      var rangeInner = doc.createElement('div')
      rangeInner.className = 'sprinkle-dp-range'

      var p1 = buildPanel(viewDate, function (d) { selectDate(d, el) })
      var p2 = buildPanel(addMonths(viewDate, 1), function (d) { selectDate(d, rangePartner) })
      grids = [p1, p2]
      rangeInner.appendChild(p1.panel)
      rangeInner.appendChild(p2.panel)
      popup.appendChild(rangeInner)

      p1.prevBtn.addEventListener('click', function () {
        p1.viewDate = addMonths(p1.viewDate, -1)
        p2.viewDate = addMonths(p1.viewDate, 1)
        renderPanel(p1)
        renderPanel(p2)
      })
      p1.nextBtn.removeEventListener('click', p1.nextBtn.onclick)
      p1.nextBtn.addEventListener('click', function () {
        p1.viewDate = addMonths(p1.viewDate, 1)
        p2.viewDate = addMonths(p1.viewDate, 1)
        renderPanel(p1)
        renderPanel(p2)
      })

      p2.prevBtn.addEventListener('click', function () {
        p2.viewDate = addMonths(p2.viewDate, -1)
        p1.viewDate = addMonths(p2.viewDate, -1)
        renderPanel(p1)
        renderPanel(p2)
      })
      p2.nextBtn.removeEventListener('click', p2.nextBtn.onclick)
      p2.nextBtn.addEventListener('click', function () {
        p2.viewDate = addMonths(p2.viewDate, 1)
        renderPanel(p2)
        p1.viewDate = addMonths(p2.viewDate, -1)
        renderPanel(p1)
      })

      if (rangePartner) {
        rangePartner._sprinkleDpShared = popup
      }
    } else {
      popup = doc.createElement('div')
      popup.className = 'sprinkle-dp'
      popup.setAttribute('role', 'dialog')
      popup.setAttribute('aria-modal', 'true')
      popup.setAttribute('aria-label', 'Choose date')
      popup.setAttribute('aria-hidden', 'true')

      var single = buildPanel(viewDate)
      grids = [single]
      popup.appendChild(single.panel)
    }

    el._sprinkleDpPopup = popup
    doc.body.appendChild(popup)

    /* ── position ── */

    function position() {
      var rect = trigger.getBoundingClientRect()
      var vh = window.innerHeight
      var pw = popup.offsetWidth || (isRange ? 560 : 280)
      var ph = popup.offsetHeight || 320

      popup.style.left = ''
      popup.style.top = ''

      if (isRange) {
        var partnerTrigger = rangePartner && rangePartner._sprinkleDpTrigger
        if (partnerTrigger) {
          var r1 = trigger.getBoundingClientRect()
          var r2 = partnerTrigger.getBoundingClientRect()
          var left = Math.min(r1.left, r2.left)
          var right = Math.max(r1.right, r2.right)
          popup.style.left = left + 'px'
          if (right + 8 > window.innerWidth) {
            popup.style.left = Math.max(8, window.innerWidth - pw - 8) + 'px'
          }
        } else {
          popup.style.left = rect.left + 'px'
        }
      } else {
        popup.style.left = rect.left + 'px'
      }

      var above = rect.top
      var below = vh - rect.bottom
      if (below >= ph + 8) {
        popup.style.top = (rect.bottom + 4) + 'px'
      } else if (above >= ph + 8) {
        popup.style.top = (rect.top - ph - 4) + 'px'
      } else {
        popup.style.top = (rect.bottom + 4) + 'px'
      }

      var finalLeft = parseFloat(popup.style.left)
      if (finalLeft + pw > window.innerWidth) {
        popup.style.left = Math.max(8, window.innerWidth - pw - 8) + 'px'
      }
      if (finalLeft < 0) popup.style.left = '8px'
    }

    /* ── open / close ── */

    function open() {
      if (popup.getAttribute('aria-hidden') === 'false') return

      var allOpen = doc.querySelectorAll('.sprinkle-dp[aria-hidden="false"],.sprinkle-dp-range-wrap[aria-hidden="false"]')
      for (var oi = 0; oi < allOpen.length; oi++) {
        if (allOpen[oi] !== popup && allOpen[oi]._sprinkleDpClose) allOpen[oi]._sprinkleDpClose()
      }

      if (isRange && rangePartner) {
        var partnerVal = rangePartner.value
        var partnerDate = parseDate(partnerVal)
        if (rangeRole === 'start') {
          grids[0].viewDate = selectedDate ? startOfMonth(selectedDate) : startOfMonth(new Date())
          grids[1].viewDate = partnerDate ? startOfMonth(partnerDate) : addMonths(grids[0].viewDate, 1)
        } else {
          grids[1].viewDate = selectedDate ? startOfMonth(selectedDate) : startOfMonth(new Date())
          grids[0].viewDate = partnerDate ? startOfMonth(partnerDate) : addMonths(grids[1].viewDate, -1)
        }
      } else {
        grids[0].viewDate = startOfMonth(selectedDate || new Date())
        grids[0].showMonths = false
      }

      renderPanel(grids[0])
      if (isRange) renderPanel(grids[1])

      popup.setAttribute('aria-hidden', 'false')
      trigger.setAttribute('aria-expanded', 'true')
      if (!popup.parentNode) doc.body.appendChild(popup)

      position()

      var focus = popup.querySelector('.sprinkle-dp-selected') ||
                  popup.querySelector('.sprinkle-dp-today:not([aria-disabled])') ||
                  popup.querySelector('.sprinkle-dp-day:not([aria-disabled])')
      if (focus) setTimeout(function () { focus.focus() }, 0)
    }

    function close() {
      popup.setAttribute('aria-hidden', 'true')
      trigger.setAttribute('aria-expanded', 'false')
      if (grids[0]) grids[0].showMonths = false
      if (popup.parentNode) popup.parentNode.removeChild(popup)
    }

    popup._sprinkleDpOpen = open
    popup._sprinkleDpClose = close

    /* ── keyboard navigation ── */

    popup.addEventListener('keydown', function (e) {
      if (popup.getAttribute('aria-hidden') === 'true') return

      if (e.key === 'Escape') {
        e.preventDefault()
        close()
        trigger.focus()
        return
      }

      var focused = doc.activeElement
      if (!focused || !focused.classList.contains('sprinkle-dp-day')) return

      var dateStr = focused.getAttribute('data-date')
      var cur = parseDate(dateStr)
      if (!cur) return

      var next = null
      switch (e.key) {
        case 'ArrowRight': next = new Date(cur.getTime() + 86400000); break
        case 'ArrowLeft':  next = new Date(cur.getTime() - 86400000); break
        case 'ArrowDown':  next = new Date(cur.getTime() + 604800000); break
        case 'ArrowUp':    next = new Date(cur.getTime() - 604800000); break
        case 'Home':       next = new Date(cur.getFullYear(), cur.getMonth(), 1); break
        case 'End':        next = new Date(cur.getFullYear(), cur.getMonth() + 1, 0); break
        case 'PageDown':   next = e.shiftKey ?
          new Date(cur.getFullYear() + 1, cur.getMonth(), cur.getDate()) :
          addMonths(cur, 1); break
        case 'PageUp':     next = e.shiftKey ?
          new Date(cur.getFullYear() - 1, cur.getMonth(), cur.getDate()) :
          addMonths(cur, -1); break
        case 'Enter':
        case ' ':
          e.preventDefault()
          var kTarget = (grids.length > 1 && grids[1].grid.contains(focused)) ? rangePartner : el
          selectDate(cur, kTarget)
          return
      }

      if (next) {
        e.preventDefault()
        var targetMonth = startOfMonth(next)
        for (var i = 0; i < grids.length; i++) {
          if (grids[i].viewDate.getTime() === targetMonth.getTime()) break
        }
        if (i === grids.length) {
          grids[0].viewDate = targetMonth
          if (isRange && grids.length > 1) {
            grids[1].viewDate = addMonths(targetMonth, 1)
          }
          grids[0].showMonths = false
          renderPanel(grids[0])
          if (isRange) renderPanel(grids[1])
        }

        var cell = popup.querySelector('[data-date="' + fmtDate(next) + '"]')
        if (cell && !cell.getAttribute('aria-disabled')) {
          var allDays = popup.querySelectorAll('.sprinkle-dp-day')
          for (var j = 0; j < allDays.length; j++) allDays[j].setAttribute('tabindex', '-1')
          cell.setAttribute('tabindex', '0')
          cell.focus()
        }
      }
    })

    /* ── event listeners ── */

    trigger.addEventListener('click', function () {
      if (popup.getAttribute('aria-hidden') === 'true') open()
      else close()
    })

    trigger.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault()
        if (popup.getAttribute('aria-hidden') === 'true') open()
        else close()
      } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault()
        open()
      }
    })

    doc.addEventListener('mousedown', function (e) {
      if (popup.getAttribute('aria-hidden') === 'true') return
      if (!popup.contains(e.target) && !trigger.contains(e.target)) {
        if (isRange && rangePartner) {
          var pt = rangePartner._sprinkleDpTrigger
          if (pt && pt.contains(e.target)) return
        }
        close()
      }
    })

    var scrollHandler = function () {
      if (popup.getAttribute('aria-hidden') === 'false') position()
    }
    window.addEventListener('scroll', scrollHandler, true)
    window.addEventListener('resize', scrollHandler)

    trigger._sprinkleDpPopup = popup
    if (isRange && rangePartner) {
      rangePartner._sprinkleDpTrigger = trigger
    }

    /* ── init ── */

    syncHiddenInput()
    renderPanel(grids[0])
    if (isRange) renderPanel(grids[1])
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

  /* ── 29. avatar / breadcrumb (CSS-only, empty handler for observer compat) ── */

  ForgeSprinkle.register('img[avatar], ul[breadcrumb]', function noop() {})

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

  /* ── 31/32. dialog: drawer / modal ── */

  ForgeSprinkle.register('dialog[drawer], dialog[modal]', function dialog(el) {
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
      var items = el.querySelectorAll('a, button, [role="menuitem"], li > a, li > button, li[role="option"], [search-box]')
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

  /* ── 40. combo-box / multi-select ── */

  ForgeSprinkle.register('select[combo-box]', function comboBox(el) {
    if (el._sprinkleComboBoxInit) return
    el._sprinkleComboBoxInit = true
    if (!el.parentNode) return

    var nativeWidth = el.offsetWidth
    el.style.display = 'none'

    var path = svgPath()
    var doc = el.ownerDocument
    var isMulti = el.multiple

    var listId = (el.name || el.id || 'combo') + '-listbox'

    var wrap = doc.createElement('div')
    wrap.className = 'sprinkle-combo-wrap'
    wrap.setAttribute('role', 'combobox')
    wrap.setAttribute('aria-haspopup', 'listbox')
    wrap.setAttribute('aria-expanded', 'false')
    wrap.setAttribute('aria-controls', listId)

    var details = doc.createElement('details')
    details.className = 'sprinkle-combo'
    details.setAttribute('dropdown', '')
    details.setAttribute('close-outside', '')

    var summary = doc.createElement('summary')
    summary.setAttribute('role', 'button')
    summary.setAttribute('tabindex', '0')

    var label = el.getAttribute('aria-label') || el.getAttribute('label') || ''
    if (!label && el.id) {
      var lblEl = doc.querySelector('label[for="' + el.id + '"]')
      if (lblEl) label = lblEl.textContent.trim()
    }
    if (!label) label = el.name || ''
    if (label) {
      wrap.setAttribute('aria-label', label)
      summary.setAttribute('aria-label', label)
    }

    var selectedSet = []

    function getSelected() {
      if (isMulti) {
        selectedSet = []
        for (var i = 0; i < el.options.length; i++) {
          if (el.options[i].selected) selectedSet.push(i)
        }
      } else {
        var si = el.selectedIndex
        selectedSet = si >= 0 ? [si] : [0]
      }
    }
    getSelected()

    function buildSummary() {
      summary.innerHTML = ''

      var lead = el.getAttribute('leading')
      if (lead) {
        var limg = doc.createElement('span')
        limg.className = 'sprinkle-icon-leading'
        limg.style.maskImage = 'url(' + path + lead + '.svg)'
        limg.style.webkitMaskImage = 'url(' + path + lead + '.svg)'
        summary.appendChild(limg)
      }

      if (isMulti) {
        var txt = doc.createElement('span')
        txt.textContent = selectedSet.length ? selectedSet.length + ' selected' : 'Select...'
        summary.appendChild(txt)
      } else {
        var opt = el.options[selectedSet[0]]
        if (opt) {
          var avatar = opt.getAttribute('data-avatar')
          if (avatar) {
            var aimg = doc.createElement('img')
            aimg.src = avatar
            aimg.setAttribute('avatar', 'sm')
            aimg.alt = ''
            summary.appendChild(aimg)
          }
          var txt2 = doc.createElement('span')
          txt2.textContent = opt.textContent
          summary.appendChild(txt2)
        }
      }

      var suff = el.getAttribute('suffix')
      if (suff) {
        var simg = doc.createElement('span')
        simg.className = 'sprinkle-icon-suffix'
        simg.style.maskImage = 'url(' + path + suff + '.svg)'
        simg.style.webkitMaskImage = 'url(' + path + suff + '.svg)'
        summary.appendChild(simg)
      }
    }

    buildSummary()

    var panel = doc.createElement('div')
    panel.setAttribute('listbox', '')
    panel.setAttribute('role', 'listbox')
    panel.id = listId
    if (isMulti) panel.setAttribute('aria-multiselectable', 'true')

    var searchable = el.hasAttribute('searchable')
    var searchInput

    function clearSearch() {
      if (!searchInput) return
      searchInput.value = ''
      var lis = panel.querySelectorAll('li[role="option"]')
      for (var i = 0; i < lis.length; i++) {
        lis[i].style.display = ''
      }
    }

    if (searchable) {
      searchInput = doc.createElement('input')
      searchInput.type = 'text'
      searchInput.setAttribute('search-box', '')
      searchInput.setAttribute('aria-label', 'Search options')
      searchInput.placeholder = el.getAttribute('search-placeholder') || 'Search options...'
      searchInput.addEventListener('input', function () {
        var q = searchInput.value.toLowerCase().trim()
        var lis = panel.querySelectorAll('li[role="option"]')
        for (var i = 0; i < lis.length; i++) {
          lis[i].style.display = (!q || lis[i].textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none'
        }
        var cats = panel.querySelectorAll('.sprinkle-combo-category')
        if (q) {
          for (var j = 0; j < cats.length; j++) {
            var cat = cats[j]
            var next = cat.nextElementSibling
            var hasVisible = false
            while (next && !next.classList.contains('sprinkle-combo-category')) {
              if (next.style.display !== 'none') { hasVisible = true; break }
              next = next.nextElementSibling
            }
            cat.style.display = hasVisible ? '' : 'none'
          }
        } else {
          for (var j = 0; j < cats.length; j++) cats[j].style.display = ''
        }
      })
      searchInput.addEventListener('click', function (e) { e.stopPropagation() })
      panel.appendChild(searchInput)
    }

    var ul = doc.createElement('ul')

    function refreshList() {
      var lis = ul.querySelectorAll('li[role="option"]')
      for (var i = 0; i < lis.length; i++) {
        lis[i].removeAttribute('active')
        lis[i].removeAttribute('aria-selected')
        var check = lis[i].querySelector('.sprinkle-combo-check')
        if (check) check.style.display = 'none'
      }
      for (var si = 0; si < selectedSet.length; si++) {
        var idx = selectedSet[si]
        var active = lis[idx]
        if (active) {
          active.setAttribute('active', '')
          active.setAttribute('aria-selected', 'true')
          var check = active.querySelector('.sprinkle-combo-check')
          if (check) check.style.display = ''
        }
      }
    }

    function selectOption(idx) {
      if (el.options[idx] && el.options[idx].disabled) return
      if (isMulti) {
        var pos = selectedSet.indexOf(idx)
        if (pos >= 0) {
          selectedSet.splice(pos, 1)
          el.options[idx].selected = false
        } else {
          selectedSet.push(idx)
          selectedSet.sort(function (a, b) { return a - b })
          el.options[idx].selected = true
        }
        el.dispatchEvent(new (window.Event || window.CustomEvent)('change', { bubbles: true }))
        buildSummary()
        refreshList()
      } else {
        selectedSet = [idx]
        el.selectedIndex = idx
        el.dispatchEvent(new (window.Event || window.CustomEvent)('change', { bubbles: true }))
        buildSummary()
        refreshList()
        clearSearch()
        details.removeAttribute('open')
      }
    }

    var currentCat = null

    function addCategory(cat) {
      if (!cat) return
      var catLi = doc.createElement('li')
      catLi.className = 'sprinkle-combo-category'
      catLi.setAttribute('role', 'separator')
      catLi.setAttribute('aria-label', cat)
      catLi.textContent = cat
      ul.appendChild(catLi)
    }

    for (var i = 0; i < el.options.length; i++) {
      var opt = el.options[i]
      var cat = opt.getAttribute('category')
      if (cat !== currentCat) {
        addCategory(cat)
        currentCat = cat
      }
      ;(function (idx) {
        var opt = el.options[idx]
        var li = doc.createElement('li')
        li.setAttribute('role', 'option')
        li.setAttribute('data-value', opt.value)
        li.setAttribute('data-category', cat || '')
        li.setAttribute('aria-posinset', String(idx + 1))
        li.setAttribute('aria-setsize', String(el.options.length))
        li.tabIndex = -1

        if (opt.disabled) {
          li.setAttribute('aria-disabled', 'true')
        }

        if (isMulti) {
          var ck = doc.createElement('span')
          ck.className = 'sprinkle-combo-check'
          ck.textContent = '✓'
          li.appendChild(ck)
        }

        var av = opt.getAttribute('data-avatar')
        if (av) {
          var img = doc.createElement('img')
          img.src = av
          img.setAttribute('avatar', 'sm')
          img.alt = ''
          li.appendChild(img)
        }

        li.appendChild(doc.createTextNode(opt.textContent))

        if (!opt.disabled) {
          li.addEventListener('click', function () { selectOption(idx) })
          li.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault()
              selectOption(idx)
            }
          })
        }
        ul.appendChild(li)
      })(i)
    }

    refreshList()

    panel.appendChild(ul)
    details.appendChild(summary)
    details.appendChild(panel)
    wrap.appendChild(details)

    if (nativeWidth > 0) wrap.style.minWidth = nativeWidth + 'px'

    details.addEventListener('toggle', function () {
      if (details.open) {
        if (searchInput) setTimeout(function () { searchInput.focus() }, 0)
        wrap.setAttribute('aria-expanded', 'true')
      } else {
        wrap.setAttribute('aria-expanded', 'false')
        clearSearch()
      }
    })

    el.addEventListener('change', function () {
      var prev = selectedSet.slice()
      getSelected()
      if (prev.length !== selectedSet.length || prev.some(function (v, i) { return v !== selectedSet[i] })) {
        buildSummary()
        refreshList()
      }
    })

    el.parentNode.insertBefore(wrap, el.nextSibling)

    matchNode(details)
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

    /* auto-add sidebar toggle if shell has a sidebar + content */
    if (left && content && !el._sprinkleShellInit) {
      el._sprinkleShellInit = true

      if (window.innerWidth <= 768) {
        el.setAttribute('sidebar-hidden', '')
      }

      var btn = document.createElement('button')
      btn.className = 'sprinkle-sidebar-toggle'
      btn.setAttribute('aria-label', 'Toggle sidebar')
      btn.textContent = '\u2630'
      btn.addEventListener('click', function () {
        el.toggleAttribute('sidebar-hidden')
      })
      content.insertBefore(btn, content.firstChild)
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
    if (el._sprinkleNavInit) return
    el._sprinkleNavInit = true

    function setAria() {
      var links = el.querySelectorAll('a[active]')
      for (var i = 0; i < links.length; i++) {
        links[i].setAttribute('aria-current', 'page')
      }
    }
    setAria()

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

  /* ── 41. card ── */

  ForgeSprinkle.register('fieldset[card]', function card(el) {
    if (el._sprinkleCardInit) return
    el._sprinkleCardInit = true

    if (!el.hasAttribute('href')) return

    el.addEventListener('click', function (e) {
      if (e.target.closest('a, button, input, textarea, select, [role="button"]')) return
      var link = el.querySelector('.card-link')
      if (link) link.click()
    })

    el.style.cursor = 'pointer'
  })

  /* ── 42. count-up ── */

  ForgeSprinkle.register('[count-up]', function countUp(el) {
    if (el._sprinkleCountUp) return
    el._sprinkleCountUp = true

    var isProgress = el.tagName === 'PROGRESS'
    var target, startVal = parseFloat(el.getAttribute('start')) || 0
    var dur = parseInt(el.getAttribute('duration'), 10) || 2000

    if (isProgress) {
      target = parseFloat(el.getAttribute('value'))
      if (isNaN(target)) return
    } else {
      var m = el.textContent.match(/^([^0-9]*)([\d,]+(?:\.\d+)?)(.*)$/)
      if (!m) return
      target = parseFloat(m[2].replace(/,/g, ''))
    }

    var io = new IntersectionObserver(function (entries) {
      if (!entries[0].isIntersecting) return
      io.disconnect()

      var startTime = null
      ;(function step(ts) {
        if (startTime === null) startTime = ts
        var p = Math.min((ts - startTime) / dur, 1)
        var eased = 1 - (1 - p) * (1 - p)
        var val = startVal + (target - startVal) * eased
        if (isProgress) {
          el.value = val
        } else {
          el.textContent = m[1] + Math.round(val).toLocaleString() + m[3]
        }
        if (p < 1) requestAnimationFrame(step)
      })(performance.now())
    }, { threshold: 0.3 })

    io.observe(el)
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
