/*!
 * @preserve
 * gascrolldepth.js | v0.9
 * Copyright (c) 2015 Rob Flaherty (@robflaherty), Leigh McCulloch (@___leigh___)
 * Licensed under the MIT and GPL licenses.
 *
 * Rob Flaherty (Scroll Depth)
 * ->
 * Leigh McCulloch (non-jQuery fork)
 * ->
 * Robert Dian (Modifications)
 * http://rodi.sk/
 */

window.performance = (window.performance || {
  offset: Date.now(),
  now: function now () {
    return Date.now() - this.offset
  }
});

(function (f, g, z) {
  function A () {
    for (var a = 1; a < arguments.length; a++) {
      for (var d in arguments[a]) arguments[a].hasOwnProperty(d) && (arguments[0][d] = arguments[a][d])
    }
    return arguments[0]
  }

  function B (a, d) {
    for (var b = 0; b < a.length; b++) {
      if (a[b] === d) return !0
    }
    return !1
  }

  function C () {
    return Math.max(g.documentElement.scrollHeight, g.body.scrollHeight, g.documentElement.offsetHeight, g.body.offsetHeight, g.documentElement.clientHeight)
  }

  function D () {
    return f.pageYOffset || (g.compatMode === 'CSS1Compat' ? g.documentElement.scrollTop :
            g.body.scrollTop)
  }

  function E (a, d, b) { a.removeEventListener ? a.removeEventListener(d, b, !1) : a.detachEvent ? a.detachEvent('on' + d, b) : a['on' + type] = null }

  function y (a) {
    v = !0
    f.addEventListener ? f.addEventListener('scroll', a, !1) : f.attachEvent ? f.attachEvent('onscroll', a) : f.onscroll = a
  }
  var F = { minHeight: 0, elements: [], percentage: !1, PiwikPercentageA: !1, PiwikPercentageB: !1, PiwikGoal: !1, PiwikTimerDelay: 5E3, userTiming: !1, pixelDepth: !1, nonInteraction: !0, gaGlobal: !1, gtmOverride: !1 },
    a = A({}, F),
    p = [],
    v = !1,
    k = 0,
    G = 0,
    H = 0,
    I = 0,
    h,
    w, J, K, m, r, t, u = function (e) {
      function d () {
        var c = a.PiwikTimerDelay - Math.round(performance.now() - M)
        return c >= 0 ? c : 0
      }

      function b (a) {
        var b = d()
        b > 100 ? setTimeout(function () { eval(a) }, b) : eval(a)
      }

      function L (c, d, n, e) {
        if (r) {
          r({ event: 'ScrollDistance', eventCategory: 'Scroll Depth', eventAction: c, eventLabel: d, eventValue: 1, eventNonInteraction: a.nonInteraction }), a.pixelDepth && arguments.length > 2 && n > k && (k = n, r({ event: 'ScrollDistance', eventCategory: 'Scroll Depth', eventAction: 'Pixel Depth', eventLabel: x(n), eventValue: 1, eventNonInteraction: a.nonInteraction })),
                    a.userTiming && arguments.length > 3 && r({ event: 'ScrollTiming', eventCategory: 'Scroll Depth', eventAction: c, eventLabel: d, eventTiming: e })
        } else {
          if (w && (f[m]('send', 'event', 'Scroll Depth', c, d, 1, { nonInteraction: a.nonInteraction }), a.pixelDepth && arguments.length > 2 && n > k && (k = n, f[m]('send', 'event', 'Scroll Depth', 'Pixel Depth', x(n), 1, { nonInteraction: a.nonInteraction })), a.userTiming && arguments.length > 3)) f[m]('send', 'timing', 'Scroll Depth', c, e, d)
          if (K) {
            h = parseInt(d, 10)
            if (a.PiwikPercentageA && h > G) {
              G = h
              var q = "_paq.push(['trackEvent', 'Scroll Depth', '" + c + "', '" + h + "%', 1]);"
              b(q)
            }
            a.PiwikPercentageB && h > H && (H = h, q = "_paq.push(['trackEvent', 'Scroll Depth', 'Percentage', '%', " + h + ']);', b(q))
            a.PiwikGoal && h > I && (I = h, q = "_paq.push(['trackGoal', " + u(h) + ', 0]);',
                            b(q))
            a.pixelDepth && arguments.length > 2 && n > k && (k = n, q = "_paq.push(['trackEvent', 'Scroll Depth', 'Pixel Depth', " + x(n) + ', 1]);', b(q))
          }
        }
      }

      function u (c) {
        return c == '00' ? a.PiwikGoal00 : c == '10' ? a.PiwikGoal10 : c == '20' ? a.PiwikGoal20 : c == '30' ? a.PiwikGoal30 : c == '40' ? a.PiwikGoal40 : c == '50' ? a.PiwikGoal50 : c == '60' ? a.PiwikGoal60 : c == '70' ? a.PiwikGoal70 : c == '80' ? a.PiwikGoal80 : c == '90' ? a.PiwikGoal90 : c == '100' ? a.PiwikGoal100 : z
      }

      function x (a) {
        return (100 * Math.floor(a / 100)).toString()
      }

      function N (a, d) {
        var b, e, f, g = null,
          l = 0,
          h = function () {
            l =
                            new Date()
            g = null
            f = a.apply(b, e)
          }
        return function () {
          var k = new Date()
          l || (l = k)
          var p = d - (k - l)
          b = this
          e = arguments
          p <= 0 ? (clearTimeout(g), g = null, l = k, f = a.apply(b, e)) : g || (g = setTimeout(h, p))
          return f
        }
      }
      var O = +new Date()
      a = A({}, F, e)
      if (!(C() < a.minHeight)) {
        typeof _paq !== 'undefined' && typeof _paq.push === 'function' && (K = !0)
        typeof a.eventHandler ===
                'function' ? r = a.eventHandler : typeof dataLayer === 'undefined' || typeof dataLayer.push !== 'function' || a.gtmOverride || (r = function (a) { dataLayer.push(a) })
        var M = performance.now()
        a.PiwikPercentageA && b("_paq.push(['trackEvent', 'Scroll Depth', 'Percentage', '00%', 1]);")
        a.PiwikPercentageB && b("_paq.push(['trackEvent', 'Scroll Depth', 'Percentage', '%', 0.01]);")
        a.PiwikGoal && (e = "_paq.push(['trackGoal', " + u('00') + ', 0]);', b(e))
        t = N(function () {
          var c = C(),
            b = f.innerHeight || g.documentElement.clientHeight ||
                        g.body.clientHeight,
            b = D() + b,
            c = { 10: parseInt(0.1 * c, 10), 20: parseInt(0.2 * c, 10), 30: parseInt(0.3 * c, 10), 40: parseInt(0.4 * c, 10), 50: parseInt(0.5 * c, 10), 60: parseInt(0.6 * c, 10), 70: parseInt(0.7 * c, 10), 80: parseInt(0.8 * c, 10), 90: parseInt(0.9 * c, 10), 100: parseInt(c - 5, 10) },
            d = +new Date() - O
          if (p.length >= 10 + a.elements.length) v = !1, E(f, 'scroll', void 0)
          else {
            if (a.elements) {
              for (var e = a.elements, k = 0; k < e.length; k++) {
                var h = e[k]
                if (!B(p, h)) {
                  var l
                  typeof h === 'string' ? (l = h, l = typeof f.jQuery !== 'undefined' ? f.jQuery(l).get(0) : typeof g.querySelector !== 'undefined' ?
                                        g.querySelector(l) : l.charAt(0) == '#' ? g.getElementById(l.substr(1)) : z) : l = h
                  l && (l = l.getBoundingClientRect().top + D(), b >= l && (L('Elements', h, b, d), p.push(h)))
                }
              }
            }
            if (a.percentage) {
              for (var m in c) c.hasOwnProperty(m) && (e = c[m], !B(p, m) && b >= e && (L('Percentage', m, b, d), p.push(m)))
            }
          }
        }, 500)
        y(t)
      }
    }
  f.gascrolldepth = {
    init: u,
    reset: function () {
      p = []
      k = 0
      if (typeof t !== 'undefined') {
        var a = t
        v = !1
        E(f, 'scroll', a)
        y(t)
      }
    },
    addElements: function (e) {
      if (typeof e !== 'undefined' && Object.prototype.toString.call(e) === '[object Array]') {
        for (var d =
                        0; d < e.length; d++) {
          var b = e[d]; a.elements.indexOf(b) == -1 && a.elements.push(b)
        }
        v || y()
      }
    },
    removeElements: function (e) {
      if (typeof e !== 'undefined' && Object.prototype.toString.call(e) === '[object Array]') {
        for (var d = 0; d < e.length; d++) {
          var b = e[d],
            f = a.elements.indexOf(b); f > -1 && a.elements.splice(f, 1)
          b = p.indexOf(b); b > -1 && p.splice(b, 1)
        }
      }
    }
  }
  typeof f.jQuery !== 'undefined' && (f.jQuery.gascrolldepth = u)
})(window, document)
