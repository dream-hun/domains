import { r as T, j as C, c as Io } from './app-C_M1-u2r.js';
import { A as Fo } from './app-logo-icon-BcSq9Noa.js';
const Je = T.createContext({});
function Qe(t) {
    const e = T.useRef(null);
    return (e.current === null && (e.current = t()), e.current);
}
const Gs = typeof window < 'u',
    _s = Gs ? T.useLayoutEffect : T.useEffect,
    ae = T.createContext(null);
function tn(t, e) {
    t.indexOf(e) === -1 && t.push(e);
}
function Qt(t, e) {
    const n = t.indexOf(e);
    n > -1 && t.splice(n, 1);
}
const Q = (t, e, n) => (n > e ? e : n < t ? t : n);
let en = () => {};
const nt = {},
    Xs = (t) => /^-?(?:\d+(?:\.\d+)?|\.\d+)$/u.test(t);
function Ys(t) {
    return typeof t == 'object' && t !== null;
}
const qs = (t) => /^0[^.\s]+$/u.test(t);
function Zs(t) {
    let e;
    return () => (e === void 0 && (e = t()), e);
}
const $ = (t) => t,
    jo = (t, e) => (n) => e(t(n)),
    jt = (...t) => t.reduce(jo),
    Rt = (t, e, n) => {
        const s = e - t;
        return s === 0 ? 1 : (n - t) / s;
    };
class nn {
    constructor() {
        this.subscriptions = [];
    }
    add(e) {
        return (tn(this.subscriptions, e), () => Qt(this.subscriptions, e));
    }
    notify(e, n, s) {
        const i = this.subscriptions.length;
        if (i)
            if (i === 1) this.subscriptions[0](e, n, s);
            else
                for (let r = 0; r < i; r++) {
                    const o = this.subscriptions[r];
                    o && o(e, n, s);
                }
    }
    getSize() {
        return this.subscriptions.length;
    }
    clear() {
        this.subscriptions.length = 0;
    }
}
const _ = (t) => t * 1e3,
    K = (t) => t / 1e3;
function Js(t, e) {
    return e ? t * (1e3 / e) : 0;
}
const Qs = (t, e, n) =>
        (((1 - 3 * n + 3 * e) * t + (3 * n - 6 * e)) * t + 3 * e) * t,
    Bo = 1e-7,
    Oo = 12;
function No(t, e, n, s, i) {
    let r,
        o,
        a = 0;
    do
        ((o = e + (n - e) / 2),
            (r = Qs(o, s, i) - t),
            r > 0 ? (n = o) : (e = o));
    while (Math.abs(r) > Bo && ++a < Oo);
    return o;
}
function Bt(t, e, n, s) {
    if (t === e && n === s) return $;
    const i = (r) => No(r, 0, 1, t, n);
    return (r) => (r === 0 || r === 1 ? r : Qs(i(r), e, s));
}
const ti = (t) => (e) => (e <= 0.5 ? t(2 * e) / 2 : (2 - t(2 * (1 - e))) / 2),
    ei = (t) => (e) => 1 - t(1 - e),
    ni = Bt(0.33, 1.53, 0.69, 0.99),
    sn = ei(ni),
    si = ti(sn),
    ii = (t) =>
        (t *= 2) < 1 ? 0.5 * sn(t) : 0.5 * (2 - Math.pow(2, -10 * (t - 1))),
    on = (t) => 1 - Math.sin(Math.acos(t)),
    oi = ei(on),
    ri = ti(on),
    Uo = Bt(0.42, 0, 1, 1),
    Wo = Bt(0, 0, 0.58, 1),
    ai = Bt(0.42, 0, 0.58, 1),
    Ko = (t) => Array.isArray(t) && typeof t[0] != 'number',
    li = (t) => Array.isArray(t) && typeof t[0] == 'number',
    $o = {
        linear: $,
        easeIn: Uo,
        easeInOut: ai,
        easeOut: Wo,
        circIn: on,
        circInOut: ri,
        circOut: oi,
        backIn: sn,
        backInOut: si,
        backOut: ni,
        anticipate: ii,
    },
    Ho = (t) => typeof t == 'string',
    En = (t) => {
        if (li(t)) {
            en(t.length === 4);
            const [e, n, s, i] = t;
            return Bt(e, n, s, i);
        } else if (Ho(t)) return $o[t];
        return t;
    },
    Ut = [
        'setup',
        'read',
        'resolveKeyframes',
        'preUpdate',
        'update',
        'preRender',
        'render',
        'postRender',
    ];
function zo(t, e) {
    let n = new Set(),
        s = new Set(),
        i = !1,
        r = !1;
    const o = new WeakSet();
    let a = { delta: 0, timestamp: 0, isProcessing: !1 };
    function l(u) {
        (o.has(u) && (c.schedule(u), t()), u(a));
    }
    const c = {
        schedule: (u, h = !1, f = !1) => {
            const m = f && i ? n : s;
            return (h && o.add(u), m.has(u) || m.add(u), u);
        },
        cancel: (u) => {
            (s.delete(u), o.delete(u));
        },
        process: (u) => {
            if (((a = u), i)) {
                r = !0;
                return;
            }
            ((i = !0),
                ([n, s] = [s, n]),
                n.forEach(l),
                n.clear(),
                (i = !1),
                r && ((r = !1), c.process(u)));
        },
    };
    return c;
}
const Go = 40;
function ci(t, e) {
    let n = !1,
        s = !0;
    const i = { delta: 0, timestamp: 0, isProcessing: !1 },
        r = () => (n = !0),
        o = Ut.reduce((v, S) => ((v[S] = zo(r)), v), {}),
        {
            setup: a,
            read: l,
            resolveKeyframes: c,
            preUpdate: u,
            update: h,
            preRender: f,
            render: d,
            postRender: m,
        } = o,
        y = () => {
            const v = nt.useManualTiming ? i.timestamp : performance.now();
            ((n = !1),
                nt.useManualTiming ||
                    (i.delta = s
                        ? 1e3 / 60
                        : Math.max(Math.min(v - i.timestamp, Go), 1)),
                (i.timestamp = v),
                (i.isProcessing = !0),
                a.process(i),
                l.process(i),
                c.process(i),
                u.process(i),
                h.process(i),
                f.process(i),
                d.process(i),
                m.process(i),
                (i.isProcessing = !1),
                n && e && ((s = !1), t(y)));
        },
        p = () => {
            ((n = !0), (s = !0), i.isProcessing || t(y));
        };
    return {
        schedule: Ut.reduce((v, S) => {
            const w = o[S];
            return (
                (v[S] = (A, M = !1, b = !1) => (n || p(), w.schedule(A, M, b))),
                v
            );
        }, {}),
        cancel: (v) => {
            for (let S = 0; S < Ut.length; S++) o[Ut[S]].cancel(v);
        },
        state: i,
        steps: o,
    };
}
const {
    schedule: D,
    cancel: rt,
    state: B,
    steps: he,
} = ci(typeof requestAnimationFrame < 'u' ? requestAnimationFrame : $, !0);
let zt;
function _o() {
    zt = void 0;
}
const U = {
        now: () => (
            zt === void 0 &&
                U.set(
                    B.isProcessing || nt.useManualTiming
                        ? B.timestamp
                        : performance.now(),
                ),
            zt
        ),
        set: (t) => {
            ((zt = t), queueMicrotask(_o));
        },
    },
    ui = (t) => (e) => typeof e == 'string' && e.startsWith(t),
    hi = ui('--'),
    Xo = ui('var(--'),
    rn = (t) => (Xo(t) ? Yo.test(t.split('/*')[0].trim()) : !1),
    Yo =
        /var\(--(?:[\w-]+\s*|[\w-]+\s*,(?:\s*[^)(\s]|\s*\((?:[^)(]|\([^)(]*\))*\))+\s*)\)$/iu;
function Rn(t) {
    return typeof t != 'string' ? !1 : t.split('/*')[0].includes('var(--');
}
const Pt = {
        test: (t) => typeof t == 'number',
        parse: parseFloat,
        transform: (t) => t,
    },
    Lt = { ...Pt, transform: (t) => Q(0, 1, t) },
    Wt = { ...Pt, default: 1 },
    Ct = (t) => Math.round(t * 1e5) / 1e5,
    an = /-?(?:\d+(?:\.\d+)?|\.\d+)/gu;
function qo(t) {
    return t == null;
}
const Zo =
        /^(?:#[\da-f]{3,8}|(?:rgb|hsl)a?\((?:-?[\d.]+%?[,\s]+){2}-?[\d.]+%?\s*(?:[,/]\s*)?(?:\b\d+(?:\.\d+)?|\.\d+)?%?\))$/iu,
    ln = (t, e) => (n) =>
        !!(
            (typeof n == 'string' && Zo.test(n) && n.startsWith(t)) ||
            (e && !qo(n) && Object.prototype.hasOwnProperty.call(n, e))
        ),
    fi = (t, e, n) => (s) => {
        if (typeof s != 'string') return s;
        const [i, r, o, a] = s.match(an);
        return {
            [t]: parseFloat(i),
            [e]: parseFloat(r),
            [n]: parseFloat(o),
            alpha: a !== void 0 ? parseFloat(a) : 1,
        };
    },
    Jo = (t) => Q(0, 255, t),
    fe = { ...Pt, transform: (t) => Math.round(Jo(t)) },
    ht = {
        test: ln('rgb', 'red'),
        parse: fi('red', 'green', 'blue'),
        transform: ({ red: t, green: e, blue: n, alpha: s = 1 }) =>
            'rgba(' +
            fe.transform(t) +
            ', ' +
            fe.transform(e) +
            ', ' +
            fe.transform(n) +
            ', ' +
            Ct(Lt.transform(s)) +
            ')',
    };
function Qo(t) {
    let e = '',
        n = '',
        s = '',
        i = '';
    return (
        t.length > 5
            ? ((e = t.substring(1, 3)),
              (n = t.substring(3, 5)),
              (s = t.substring(5, 7)),
              (i = t.substring(7, 9)))
            : ((e = t.substring(1, 2)),
              (n = t.substring(2, 3)),
              (s = t.substring(3, 4)),
              (i = t.substring(4, 5)),
              (e += e),
              (n += n),
              (s += s),
              (i += i)),
        {
            red: parseInt(e, 16),
            green: parseInt(n, 16),
            blue: parseInt(s, 16),
            alpha: i ? parseInt(i, 16) / 255 : 1,
        }
    );
}
const Me = { test: ln('#'), parse: Qo, transform: ht.transform },
    Ot = (t) => ({
        test: (e) =>
            typeof e == 'string' && e.endsWith(t) && e.split(' ').length === 1,
        parse: parseFloat,
        transform: (e) => `${e}${t}`,
    }),
    it = Ot('deg'),
    J = Ot('%'),
    P = Ot('px'),
    tr = Ot('vh'),
    er = Ot('vw'),
    Ln = {
        ...J,
        parse: (t) => J.parse(t) / 100,
        transform: (t) => J.transform(t * 100),
    },
    pt = {
        test: ln('hsl', 'hue'),
        parse: fi('hue', 'saturation', 'lightness'),
        transform: ({ hue: t, saturation: e, lightness: n, alpha: s = 1 }) =>
            'hsla(' +
            Math.round(t) +
            ', ' +
            J.transform(Ct(e)) +
            ', ' +
            J.transform(Ct(n)) +
            ', ' +
            Ct(Lt.transform(s)) +
            ')',
    },
    I = {
        test: (t) => ht.test(t) || Me.test(t) || pt.test(t),
        parse: (t) =>
            ht.test(t) ? ht.parse(t) : pt.test(t) ? pt.parse(t) : Me.parse(t),
        transform: (t) =>
            typeof t == 'string'
                ? t
                : t.hasOwnProperty('red')
                  ? ht.transform(t)
                  : pt.transform(t),
        getAnimatableNone: (t) => {
            const e = I.parse(t);
            return ((e.alpha = 0), I.transform(e));
        },
    },
    nr =
        /(?:#[\da-f]{3,8}|(?:rgb|hsl)a?\((?:-?[\d.]+%?[,\s]+){2}-?[\d.]+%?\s*(?:[,/]\s*)?(?:\b\d+(?:\.\d+)?|\.\d+)?%?\))/giu;
function sr(t) {
    return (
        isNaN(t) &&
        typeof t == 'string' &&
        (t.match(an)?.length || 0) + (t.match(nr)?.length || 0) > 0
    );
}
const di = 'number',
    mi = 'color',
    ir = 'var',
    or = 'var(',
    kn = '${}',
    rr =
        /var\s*\(\s*--(?:[\w-]+\s*|[\w-]+\s*,(?:\s*[^)(\s]|\s*\((?:[^)(]|\([^)(]*\))*\))+\s*)\)|#[\da-f]{3,8}|(?:rgb|hsl)a?\((?:-?[\d.]+%?[,\s]+){2}-?[\d.]+%?\s*(?:[,/]\s*)?(?:\b\d+(?:\.\d+)?|\.\d+)?%?\)|-?(?:\d+(?:\.\d+)?|\.\d+)/giu;
function kt(t) {
    const e = t.toString(),
        n = [],
        s = { color: [], number: [], var: [] },
        i = [];
    let r = 0;
    const a = e
        .replace(
            rr,
            (l) => (
                I.test(l)
                    ? (s.color.push(r), i.push(mi), n.push(I.parse(l)))
                    : l.startsWith(or)
                      ? (s.var.push(r), i.push(ir), n.push(l))
                      : (s.number.push(r), i.push(di), n.push(parseFloat(l))),
                ++r,
                kn
            ),
        )
        .split(kn);
    return { values: n, split: a, indexes: s, types: i };
}
function pi(t) {
    return kt(t).values;
}
function gi(t) {
    const { split: e, types: n } = kt(t),
        s = e.length;
    return (i) => {
        let r = '';
        for (let o = 0; o < s; o++)
            if (((r += e[o]), i[o] !== void 0)) {
                const a = n[o];
                a === di
                    ? (r += Ct(i[o]))
                    : a === mi
                      ? (r += I.transform(i[o]))
                      : (r += i[o]);
            }
        return r;
    };
}
const ar = (t) =>
    typeof t == 'number' ? 0 : I.test(t) ? I.getAnimatableNone(t) : t;
function lr(t) {
    const e = pi(t);
    return gi(t)(e.map(ar));
}
const X = { test: sr, parse: pi, createTransformer: gi, getAnimatableNone: lr };
function de(t, e, n) {
    return (
        n < 0 && (n += 1),
        n > 1 && (n -= 1),
        n < 1 / 6
            ? t + (e - t) * 6 * n
            : n < 1 / 2
              ? e
              : n < 2 / 3
                ? t + (e - t) * (2 / 3 - n) * 6
                : t
    );
}
function cr({ hue: t, saturation: e, lightness: n, alpha: s }) {
    ((t /= 360), (e /= 100), (n /= 100));
    let i = 0,
        r = 0,
        o = 0;
    if (!e) i = r = o = n;
    else {
        const a = n < 0.5 ? n * (1 + e) : n + e - n * e,
            l = 2 * n - a;
        ((i = de(l, a, t + 1 / 3)),
            (r = de(l, a, t)),
            (o = de(l, a, t - 1 / 3)));
    }
    return {
        red: Math.round(i * 255),
        green: Math.round(r * 255),
        blue: Math.round(o * 255),
        alpha: s,
    };
}
function te(t, e) {
    return (n) => (n > 0 ? e : t);
}
const L = (t, e, n) => t + (e - t) * n,
    me = (t, e, n) => {
        const s = t * t,
            i = n * (e * e - s) + s;
        return i < 0 ? 0 : Math.sqrt(i);
    },
    ur = [Me, ht, pt],
    hr = (t) => ur.find((e) => e.test(t));
function In(t) {
    const e = hr(t);
    if (!e) return !1;
    let n = e.parse(t);
    return (e === pt && (n = cr(n)), n);
}
const Fn = (t, e) => {
        const n = In(t),
            s = In(e);
        if (!n || !s) return te(t, e);
        const i = { ...n };
        return (r) => (
            (i.red = me(n.red, s.red, r)),
            (i.green = me(n.green, s.green, r)),
            (i.blue = me(n.blue, s.blue, r)),
            (i.alpha = L(n.alpha, s.alpha, r)),
            ht.transform(i)
        );
    },
    De = new Set(['none', 'hidden']);
function fr(t, e) {
    return De.has(t) ? (n) => (n <= 0 ? t : e) : (n) => (n >= 1 ? e : t);
}
function dr(t, e) {
    return (n) => L(t, e, n);
}
function cn(t) {
    return typeof t == 'number'
        ? dr
        : typeof t == 'string'
          ? rn(t)
              ? te
              : I.test(t)
                ? Fn
                : gr
          : Array.isArray(t)
            ? yi
            : typeof t == 'object'
              ? I.test(t)
                  ? Fn
                  : mr
              : te;
}
function yi(t, e) {
    const n = [...t],
        s = n.length,
        i = t.map((r, o) => cn(r)(r, e[o]));
    return (r) => {
        for (let o = 0; o < s; o++) n[o] = i[o](r);
        return n;
    };
}
function mr(t, e) {
    const n = { ...t, ...e },
        s = {};
    for (const i in n)
        t[i] !== void 0 && e[i] !== void 0 && (s[i] = cn(t[i])(t[i], e[i]));
    return (i) => {
        for (const r in s) n[r] = s[r](i);
        return n;
    };
}
function pr(t, e) {
    const n = [],
        s = { color: 0, var: 0, number: 0 };
    for (let i = 0; i < e.values.length; i++) {
        const r = e.types[i],
            o = t.indexes[r][s[r]],
            a = t.values[o] ?? 0;
        ((n[i] = a), s[r]++);
    }
    return n;
}
const gr = (t, e) => {
    const n = X.createTransformer(e),
        s = kt(t),
        i = kt(e);
    return s.indexes.var.length === i.indexes.var.length &&
        s.indexes.color.length === i.indexes.color.length &&
        s.indexes.number.length >= i.indexes.number.length
        ? (De.has(t) && !i.values.length) || (De.has(e) && !s.values.length)
            ? fr(t, e)
            : jt(yi(pr(s, i), i.values), n)
        : te(t, e);
};
function vi(t, e, n) {
    return typeof t == 'number' && typeof e == 'number' && typeof n == 'number'
        ? L(t, e, n)
        : cn(t)(t, e);
}
const yr = (t) => {
        const e = ({ timestamp: n }) => t(n);
        return {
            start: (n = !0) => D.update(e, n),
            stop: () => rt(e),
            now: () => (B.isProcessing ? B.timestamp : U.now()),
        };
    },
    xi = (t, e, n = 10) => {
        let s = '';
        const i = Math.max(Math.round(e / n), 2);
        for (let r = 0; r < i; r++)
            s += Math.round(t(r / (i - 1)) * 1e4) / 1e4 + ', ';
        return `linear(${s.substring(0, s.length - 2)})`;
    },
    ee = 2e4;
function un(t) {
    let e = 0;
    const n = 50;
    let s = t.next(e);
    for (; !s.done && e < ee; ) ((e += n), (s = t.next(e)));
    return e >= ee ? 1 / 0 : e;
}
function vr(t, e = 100, n) {
    const s = n({ ...t, keyframes: [0, e] }),
        i = Math.min(un(s), ee);
    return {
        type: 'keyframes',
        ease: (r) => s.next(i * r).value / e,
        duration: K(i),
    };
}
const xr = 5;
function Ti(t, e, n) {
    const s = Math.max(e - xr, 0);
    return Js(n - t(s), e - s);
}
const k = {
        stiffness: 100,
        damping: 10,
        mass: 1,
        velocity: 0,
        duration: 800,
        bounce: 0.3,
        visualDuration: 0.3,
        restSpeed: { granular: 0.01, default: 2 },
        restDelta: { granular: 0.005, default: 0.5 },
        minDuration: 0.01,
        maxDuration: 10,
        minDamping: 0.05,
        maxDamping: 1,
    },
    pe = 0.001;
function Tr({
    duration: t = k.duration,
    bounce: e = k.bounce,
    velocity: n = k.velocity,
    mass: s = k.mass,
}) {
    let i,
        r,
        o = 1 - e;
    ((o = Q(k.minDamping, k.maxDamping, o)),
        (t = Q(k.minDuration, k.maxDuration, K(t))),
        o < 1
            ? ((i = (c) => {
                  const u = c * o,
                      h = u * t,
                      f = u - n,
                      d = Ee(c, o),
                      m = Math.exp(-h);
                  return pe - (f / d) * m;
              }),
              (r = (c) => {
                  const h = c * o * t,
                      f = h * n + n,
                      d = Math.pow(o, 2) * Math.pow(c, 2) * t,
                      m = Math.exp(-h),
                      y = Ee(Math.pow(c, 2), o);
                  return ((-i(c) + pe > 0 ? -1 : 1) * ((f - d) * m)) / y;
              }))
            : ((i = (c) => {
                  const u = Math.exp(-c * t),
                      h = (c - n) * t + 1;
                  return -pe + u * h;
              }),
              (r = (c) => {
                  const u = Math.exp(-c * t),
                      h = (n - c) * (t * t);
                  return u * h;
              })));
    const a = 5 / t,
        l = Pr(i, r, a);
    if (((t = _(t)), isNaN(l)))
        return { stiffness: k.stiffness, damping: k.damping, duration: t };
    {
        const c = Math.pow(l, 2) * s;
        return { stiffness: c, damping: o * 2 * Math.sqrt(s * c), duration: t };
    }
}
const wr = 12;
function Pr(t, e, n) {
    let s = n;
    for (let i = 1; i < wr; i++) s = s - t(s) / e(s);
    return s;
}
function Ee(t, e) {
    return t * Math.sqrt(1 - e * e);
}
const Sr = ['duration', 'bounce'],
    br = ['stiffness', 'damping', 'mass'];
function jn(t, e) {
    return e.some((n) => t[n] !== void 0);
}
function Ar(t) {
    let e = {
        velocity: k.velocity,
        stiffness: k.stiffness,
        damping: k.damping,
        mass: k.mass,
        isResolvedFromDuration: !1,
        ...t,
    };
    if (!jn(t, br) && jn(t, Sr))
        if (((e.velocity = 0), t.visualDuration)) {
            const n = t.visualDuration,
                s = (2 * Math.PI) / (n * 1.2),
                i = s * s,
                r = 2 * Q(0.05, 1, 1 - (t.bounce || 0)) * Math.sqrt(i);
            e = { ...e, mass: k.mass, stiffness: i, damping: r };
        } else {
            const n = Tr({ ...t, velocity: 0 });
            ((e = { ...e, ...n, mass: k.mass }),
                (e.isResolvedFromDuration = !0));
        }
    return e;
}
function ne(t = k.visualDuration, e = k.bounce) {
    const n =
        typeof t != 'object'
            ? { visualDuration: t, keyframes: [0, 1], bounce: e }
            : t;
    let { restSpeed: s, restDelta: i } = n;
    const r = n.keyframes[0],
        o = n.keyframes[n.keyframes.length - 1],
        a = { done: !1, value: r },
        {
            stiffness: l,
            damping: c,
            mass: u,
            duration: h,
            velocity: f,
            isResolvedFromDuration: d,
        } = Ar({ ...n, velocity: -K(n.velocity || 0) }),
        m = f || 0,
        y = c / (2 * Math.sqrt(l * u)),
        p = o - r,
        g = K(Math.sqrt(l / u)),
        x = Math.abs(p) < 5;
    (s || (s = x ? k.restSpeed.granular : k.restSpeed.default),
        i || (i = x ? k.restDelta.granular : k.restDelta.default));
    let v;
    if (y < 1) {
        const w = Ee(g, y);
        v = (A) => {
            const M = Math.exp(-y * g * A);
            return (
                o -
                M *
                    (((m + y * g * p) / w) * Math.sin(w * A) +
                        p * Math.cos(w * A))
            );
        };
    } else if (y === 1) v = (w) => o - Math.exp(-g * w) * (p + (m + g * p) * w);
    else {
        const w = g * Math.sqrt(y * y - 1);
        v = (A) => {
            const M = Math.exp(-y * g * A),
                b = Math.min(w * A, 300);
            return (
                o -
                (M * ((m + y * g * p) * Math.sinh(b) + w * p * Math.cosh(b))) /
                    w
            );
        };
    }
    const S = {
        calculatedDuration: (d && h) || null,
        next: (w) => {
            const A = v(w);
            if (d) a.done = w >= h;
            else {
                let M = w === 0 ? m : 0;
                y < 1 && (M = w === 0 ? _(m) : Ti(v, w, A));
                const b = Math.abs(M) <= s,
                    V = Math.abs(o - A) <= i;
                a.done = b && V;
            }
            return ((a.value = a.done ? o : A), a);
        },
        toString: () => {
            const w = Math.min(un(S), ee),
                A = xi((M) => S.next(w * M).value, w, 30);
            return w + 'ms ' + A;
        },
        toTransition: () => {},
    };
    return S;
}
ne.applyToOptions = (t) => {
    const e = vr(t, 100, ne);
    return (
        (t.ease = e.ease),
        (t.duration = _(e.duration)),
        (t.type = 'keyframes'),
        t
    );
};
function Re({
    keyframes: t,
    velocity: e = 0,
    power: n = 0.8,
    timeConstant: s = 325,
    bounceDamping: i = 10,
    bounceStiffness: r = 500,
    modifyTarget: o,
    min: a,
    max: l,
    restDelta: c = 0.5,
    restSpeed: u,
}) {
    const h = t[0],
        f = { done: !1, value: h },
        d = (b) => (a !== void 0 && b < a) || (l !== void 0 && b > l),
        m = (b) =>
            a === void 0
                ? l
                : l === void 0 || Math.abs(a - b) < Math.abs(l - b)
                  ? a
                  : l;
    let y = n * e;
    const p = h + y,
        g = o === void 0 ? p : o(p);
    g !== p && (y = g - h);
    const x = (b) => -y * Math.exp(-b / s),
        v = (b) => g + x(b),
        S = (b) => {
            const V = x(b),
                E = v(b);
            ((f.done = Math.abs(V) <= c), (f.value = f.done ? g : E));
        };
    let w, A;
    const M = (b) => {
        d(f.value) &&
            ((w = b),
            (A = ne({
                keyframes: [f.value, m(f.value)],
                velocity: Ti(v, b, f.value),
                damping: i,
                stiffness: r,
                restDelta: c,
                restSpeed: u,
            })));
    };
    return (
        M(0),
        {
            calculatedDuration: null,
            next: (b) => {
                let V = !1;
                return (
                    !A && w === void 0 && ((V = !0), S(b), M(b)),
                    w !== void 0 && b >= w ? A.next(b - w) : (!V && S(b), f)
                );
            },
        }
    );
}
function Vr(t, e, n) {
    const s = [],
        i = n || nt.mix || vi,
        r = t.length - 1;
    for (let o = 0; o < r; o++) {
        let a = i(t[o], t[o + 1]);
        if (e) {
            const l = Array.isArray(e) ? e[o] || $ : e;
            a = jt(l, a);
        }
        s.push(a);
    }
    return s;
}
function Cr(t, e, { clamp: n = !0, ease: s, mixer: i } = {}) {
    const r = t.length;
    if ((en(r === e.length), r === 1)) return () => e[0];
    if (r === 2 && e[0] === e[1]) return () => e[1];
    const o = t[0] === t[1];
    t[0] > t[r - 1] && ((t = [...t].reverse()), (e = [...e].reverse()));
    const a = Vr(e, s, i),
        l = a.length,
        c = (u) => {
            if (o && u < t[0]) return e[0];
            let h = 0;
            if (l > 1) for (; h < t.length - 2 && !(u < t[h + 1]); h++);
            const f = Rt(t[h], t[h + 1], u);
            return a[h](f);
        };
    return n ? (u) => c(Q(t[0], t[r - 1], u)) : c;
}
function Mr(t, e) {
    const n = t[t.length - 1];
    for (let s = 1; s <= e; s++) {
        const i = Rt(0, e, s);
        t.push(L(n, 1, i));
    }
}
function Dr(t) {
    const e = [0];
    return (Mr(e, t.length - 1), e);
}
function Er(t, e) {
    return t.map((n) => n * e);
}
function Rr(t, e) {
    return t.map(() => e || ai).splice(0, t.length - 1);
}
function Mt({
    duration: t = 300,
    keyframes: e,
    times: n,
    ease: s = 'easeInOut',
}) {
    const i = Ko(s) ? s.map(En) : En(s),
        r = { done: !1, value: e[0] },
        o = Er(n && n.length === e.length ? n : Dr(e), t),
        a = Cr(o, e, { ease: Array.isArray(i) ? i : Rr(e, i) });
    return {
        calculatedDuration: t,
        next: (l) => ((r.value = a(l)), (r.done = l >= t), r),
    };
}
const Lr = (t) => t !== null;
function hn(t, { repeat: e, repeatType: n = 'loop' }, s, i = 1) {
    const r = t.filter(Lr),
        a = i < 0 || (e && n !== 'loop' && e % 2 === 1) ? 0 : r.length - 1;
    return !a || s === void 0 ? r[a] : s;
}
const kr = { decay: Re, inertia: Re, tween: Mt, keyframes: Mt, spring: ne };
function wi(t) {
    typeof t.type == 'string' && (t.type = kr[t.type]);
}
class fn {
    constructor() {
        this.updateFinished();
    }
    get finished() {
        return this._finished;
    }
    updateFinished() {
        this._finished = new Promise((e) => {
            this.resolve = e;
        });
    }
    notifyFinished() {
        this.resolve();
    }
    then(e, n) {
        return this.finished.then(e, n);
    }
}
const Ir = (t) => t / 100;
class dn extends fn {
    constructor(e) {
        (super(),
            (this.state = 'idle'),
            (this.startTime = null),
            (this.isStopped = !1),
            (this.currentTime = 0),
            (this.holdTime = null),
            (this.playbackSpeed = 1),
            (this.stop = () => {
                const { motionValue: n } = this.options;
                (n && n.updatedAt !== U.now() && this.tick(U.now()),
                    (this.isStopped = !0),
                    this.state !== 'idle' &&
                        (this.teardown(), this.options.onStop?.()));
            }),
            (this.options = e),
            this.initAnimation(),
            this.play(),
            e.autoplay === !1 && this.pause());
    }
    initAnimation() {
        const { options: e } = this;
        wi(e);
        const {
            type: n = Mt,
            repeat: s = 0,
            repeatDelay: i = 0,
            repeatType: r,
            velocity: o = 0,
        } = e;
        let { keyframes: a } = e;
        const l = n || Mt;
        l !== Mt &&
            typeof a[0] != 'number' &&
            ((this.mixKeyframes = jt(Ir, vi(a[0], a[1]))), (a = [0, 100]));
        const c = l({ ...e, keyframes: a });
        (r === 'mirror' &&
            (this.mirroredGenerator = l({
                ...e,
                keyframes: [...a].reverse(),
                velocity: -o,
            })),
            c.calculatedDuration === null && (c.calculatedDuration = un(c)));
        const { calculatedDuration: u } = c;
        ((this.calculatedDuration = u),
            (this.resolvedDuration = u + i),
            (this.totalDuration = this.resolvedDuration * (s + 1) - i),
            (this.generator = c));
    }
    updateTime(e) {
        const n = Math.round(e - this.startTime) * this.playbackSpeed;
        this.holdTime !== null
            ? (this.currentTime = this.holdTime)
            : (this.currentTime = n);
    }
    tick(e, n = !1) {
        const {
            generator: s,
            totalDuration: i,
            mixKeyframes: r,
            mirroredGenerator: o,
            resolvedDuration: a,
            calculatedDuration: l,
        } = this;
        if (this.startTime === null) return s.next(0);
        const {
            delay: c = 0,
            keyframes: u,
            repeat: h,
            repeatType: f,
            repeatDelay: d,
            type: m,
            onUpdate: y,
            finalKeyframe: p,
        } = this.options;
        (this.speed > 0
            ? (this.startTime = Math.min(this.startTime, e))
            : this.speed < 0 &&
              (this.startTime = Math.min(e - i / this.speed, this.startTime)),
            n ? (this.currentTime = e) : this.updateTime(e));
        const g = this.currentTime - c * (this.playbackSpeed >= 0 ? 1 : -1),
            x = this.playbackSpeed >= 0 ? g < 0 : g > i;
        ((this.currentTime = Math.max(g, 0)),
            this.state === 'finished' &&
                this.holdTime === null &&
                (this.currentTime = i));
        let v = this.currentTime,
            S = s;
        if (h) {
            const b = Math.min(this.currentTime, i) / a;
            let V = Math.floor(b),
                E = b % 1;
            (!E && b >= 1 && (E = 1),
                E === 1 && V--,
                (V = Math.min(V, h + 1)),
                V % 2 &&
                    (f === 'reverse'
                        ? ((E = 1 - E), d && (E -= d / a))
                        : f === 'mirror' && (S = o)),
                (v = Q(0, 1, E) * a));
        }
        const w = x ? { done: !1, value: u[0] } : S.next(v);
        r && (w.value = r(w.value));
        let { done: A } = w;
        !x &&
            l !== null &&
            (A =
                this.playbackSpeed >= 0
                    ? this.currentTime >= i
                    : this.currentTime <= 0);
        const M =
            this.holdTime === null &&
            (this.state === 'finished' || (this.state === 'running' && A));
        return (
            M && m !== Re && (w.value = hn(u, this.options, p, this.speed)),
            y && y(w.value),
            M && this.finish(),
            w
        );
    }
    then(e, n) {
        return this.finished.then(e, n);
    }
    get duration() {
        return K(this.calculatedDuration);
    }
    get iterationDuration() {
        const { delay: e = 0 } = this.options || {};
        return this.duration + K(e);
    }
    get time() {
        return K(this.currentTime);
    }
    set time(e) {
        ((e = _(e)),
            (this.currentTime = e),
            this.startTime === null ||
            this.holdTime !== null ||
            this.playbackSpeed === 0
                ? (this.holdTime = e)
                : this.driver &&
                  (this.startTime = this.driver.now() - e / this.playbackSpeed),
            this.driver?.start(!1));
    }
    get speed() {
        return this.playbackSpeed;
    }
    set speed(e) {
        this.updateTime(U.now());
        const n = this.playbackSpeed !== e;
        ((this.playbackSpeed = e), n && (this.time = K(this.currentTime)));
    }
    play() {
        if (this.isStopped) return;
        const { driver: e = yr, startTime: n } = this.options;
        (this.driver || (this.driver = e((i) => this.tick(i))),
            this.options.onPlay?.());
        const s = this.driver.now();
        (this.state === 'finished'
            ? (this.updateFinished(), (this.startTime = s))
            : this.holdTime !== null
              ? (this.startTime = s - this.holdTime)
              : this.startTime || (this.startTime = n ?? s),
            this.state === 'finished' &&
                this.speed < 0 &&
                (this.startTime += this.calculatedDuration),
            (this.holdTime = null),
            (this.state = 'running'),
            this.driver.start());
    }
    pause() {
        ((this.state = 'paused'),
            this.updateTime(U.now()),
            (this.holdTime = this.currentTime));
    }
    complete() {
        (this.state !== 'running' && this.play(),
            (this.state = 'finished'),
            (this.holdTime = null));
    }
    finish() {
        (this.notifyFinished(),
            this.teardown(),
            (this.state = 'finished'),
            this.options.onComplete?.());
    }
    cancel() {
        ((this.holdTime = null),
            (this.startTime = 0),
            this.tick(0),
            this.teardown(),
            this.options.onCancel?.());
    }
    teardown() {
        ((this.state = 'idle'),
            this.stopDriver(),
            (this.startTime = this.holdTime = null));
    }
    stopDriver() {
        this.driver && (this.driver.stop(), (this.driver = void 0));
    }
    sample(e) {
        return ((this.startTime = 0), this.tick(e, !0));
    }
    attachTimeline(e) {
        return (
            this.options.allowFlatten &&
                ((this.options.type = 'keyframes'),
                (this.options.ease = 'linear'),
                this.initAnimation()),
            this.driver?.stop(),
            e.observe(this)
        );
    }
}
function Fr(t) {
    for (let e = 1; e < t.length; e++) t[e] ?? (t[e] = t[e - 1]);
}
const ft = (t) => (t * 180) / Math.PI,
    Le = (t) => {
        const e = ft(Math.atan2(t[1], t[0]));
        return ke(e);
    },
    jr = {
        x: 4,
        y: 5,
        translateX: 4,
        translateY: 5,
        scaleX: 0,
        scaleY: 3,
        scale: (t) => (Math.abs(t[0]) + Math.abs(t[3])) / 2,
        rotate: Le,
        rotateZ: Le,
        skewX: (t) => ft(Math.atan(t[1])),
        skewY: (t) => ft(Math.atan(t[2])),
        skew: (t) => (Math.abs(t[1]) + Math.abs(t[2])) / 2,
    },
    ke = (t) => ((t = t % 360), t < 0 && (t += 360), t),
    Bn = Le,
    On = (t) => Math.sqrt(t[0] * t[0] + t[1] * t[1]),
    Nn = (t) => Math.sqrt(t[4] * t[4] + t[5] * t[5]),
    Br = {
        x: 12,
        y: 13,
        z: 14,
        translateX: 12,
        translateY: 13,
        translateZ: 14,
        scaleX: On,
        scaleY: Nn,
        scale: (t) => (On(t) + Nn(t)) / 2,
        rotateX: (t) => ke(ft(Math.atan2(t[6], t[5]))),
        rotateY: (t) => ke(ft(Math.atan2(-t[2], t[0]))),
        rotateZ: Bn,
        rotate: Bn,
        skewX: (t) => ft(Math.atan(t[4])),
        skewY: (t) => ft(Math.atan(t[1])),
        skew: (t) => (Math.abs(t[1]) + Math.abs(t[4])) / 2,
    };
function Ie(t) {
    return t.includes('scale') ? 1 : 0;
}
function Fe(t, e) {
    if (!t || t === 'none') return Ie(e);
    const n = t.match(/^matrix3d\(([-\d.e\s,]+)\)$/u);
    let s, i;
    if (n) ((s = Br), (i = n));
    else {
        const a = t.match(/^matrix\(([-\d.e\s,]+)\)$/u);
        ((s = jr), (i = a));
    }
    if (!i) return Ie(e);
    const r = s[e],
        o = i[1].split(',').map(Nr);
    return typeof r == 'function' ? r(o) : o[r];
}
const Or = (t, e) => {
    const { transform: n = 'none' } = getComputedStyle(t);
    return Fe(n, e);
};
function Nr(t) {
    return parseFloat(t.trim());
}
const St = [
        'transformPerspective',
        'x',
        'y',
        'z',
        'translateX',
        'translateY',
        'translateZ',
        'scale',
        'scaleX',
        'scaleY',
        'rotate',
        'rotateX',
        'rotateY',
        'rotateZ',
        'skew',
        'skewX',
        'skewY',
    ],
    bt = new Set(St),
    Un = (t) => t === Pt || t === P,
    Ur = new Set(['x', 'y', 'z']),
    Wr = St.filter((t) => !Ur.has(t));
function Kr(t) {
    const e = [];
    return (
        Wr.forEach((n) => {
            const s = t.getValue(n);
            s !== void 0 &&
                (e.push([n, s.get()]), s.set(n.startsWith('scale') ? 1 : 0));
        }),
        e
    );
}
const ot = {
    width: ({ x: t }, { paddingLeft: e = '0', paddingRight: n = '0' }) =>
        t.max - t.min - parseFloat(e) - parseFloat(n),
    height: ({ y: t }, { paddingTop: e = '0', paddingBottom: n = '0' }) =>
        t.max - t.min - parseFloat(e) - parseFloat(n),
    top: (t, { top: e }) => parseFloat(e),
    left: (t, { left: e }) => parseFloat(e),
    bottom: ({ y: t }, { top: e }) => parseFloat(e) + (t.max - t.min),
    right: ({ x: t }, { left: e }) => parseFloat(e) + (t.max - t.min),
    x: (t, { transform: e }) => Fe(e, 'x'),
    y: (t, { transform: e }) => Fe(e, 'y'),
};
ot.translateX = ot.x;
ot.translateY = ot.y;
const dt = new Set();
let je = !1,
    Be = !1,
    Oe = !1;
function Pi() {
    if (Be) {
        const t = Array.from(dt).filter((s) => s.needsMeasurement),
            e = new Set(t.map((s) => s.element)),
            n = new Map();
        (e.forEach((s) => {
            const i = Kr(s);
            i.length && (n.set(s, i), s.render());
        }),
            t.forEach((s) => s.measureInitialState()),
            e.forEach((s) => {
                s.render();
                const i = n.get(s);
                i &&
                    i.forEach(([r, o]) => {
                        s.getValue(r)?.set(o);
                    });
            }),
            t.forEach((s) => s.measureEndState()),
            t.forEach((s) => {
                s.suspendedScrollY !== void 0 &&
                    window.scrollTo(0, s.suspendedScrollY);
            }));
    }
    ((Be = !1), (je = !1), dt.forEach((t) => t.complete(Oe)), dt.clear());
}
function Si() {
    dt.forEach((t) => {
        (t.readKeyframes(), t.needsMeasurement && (Be = !0));
    });
}
function $r() {
    ((Oe = !0), Si(), Pi(), (Oe = !1));
}
class mn {
    constructor(e, n, s, i, r, o = !1) {
        ((this.state = 'pending'),
            (this.isAsync = !1),
            (this.needsMeasurement = !1),
            (this.unresolvedKeyframes = [...e]),
            (this.onComplete = n),
            (this.name = s),
            (this.motionValue = i),
            (this.element = r),
            (this.isAsync = o));
    }
    scheduleResolve() {
        ((this.state = 'scheduled'),
            this.isAsync
                ? (dt.add(this),
                  je || ((je = !0), D.read(Si), D.resolveKeyframes(Pi)))
                : (this.readKeyframes(), this.complete()));
    }
    readKeyframes() {
        const {
            unresolvedKeyframes: e,
            name: n,
            element: s,
            motionValue: i,
        } = this;
        if (e[0] === null) {
            const r = i?.get(),
                o = e[e.length - 1];
            if (r !== void 0) e[0] = r;
            else if (s && n) {
                const a = s.readValue(n, o);
                a != null && (e[0] = a);
            }
            (e[0] === void 0 && (e[0] = o), i && r === void 0 && i.set(e[0]));
        }
        Fr(e);
    }
    setFinalKeyframe() {}
    measureInitialState() {}
    renderEndStyles() {}
    measureEndState() {}
    complete(e = !1) {
        ((this.state = 'complete'),
            this.onComplete(this.unresolvedKeyframes, this.finalKeyframe, e),
            dt.delete(this));
    }
    cancel() {
        this.state === 'scheduled' &&
            (dt.delete(this), (this.state = 'pending'));
    }
    resume() {
        this.state === 'pending' && this.scheduleResolve();
    }
}
const Hr = (t) => t.startsWith('--');
function zr(t, e, n) {
    Hr(e) ? t.style.setProperty(e, n) : (t.style[e] = n);
}
const Gr = {};
function bi(t, e) {
    const n = Zs(t);
    return () => Gr[e] ?? n();
}
const _r = bi(() => window.ScrollTimeline !== void 0, 'scrollTimeline'),
    Ai = bi(() => {
        try {
            document
                .createElement('div')
                .animate({ opacity: 0 }, { easing: 'linear(0, 1)' });
        } catch {
            return !1;
        }
        return !0;
    }, 'linearEasing'),
    Vt = ([t, e, n, s]) => `cubic-bezier(${t}, ${e}, ${n}, ${s})`,
    Wn = {
        linear: 'linear',
        ease: 'ease',
        easeIn: 'ease-in',
        easeOut: 'ease-out',
        easeInOut: 'ease-in-out',
        circIn: Vt([0, 0.65, 0.55, 1]),
        circOut: Vt([0.55, 0, 1, 0.45]),
        backIn: Vt([0.31, 0.01, 0.66, -0.59]),
        backOut: Vt([0.33, 1.53, 0.69, 0.99]),
    };
function Vi(t, e) {
    if (t)
        return typeof t == 'function'
            ? Ai()
                ? xi(t, e)
                : 'ease-out'
            : li(t)
              ? Vt(t)
              : Array.isArray(t)
                ? t.map((n) => Vi(n, e) || Wn.easeOut)
                : Wn[t];
}
function Xr(
    t,
    e,
    n,
    {
        delay: s = 0,
        duration: i = 300,
        repeat: r = 0,
        repeatType: o = 'loop',
        ease: a = 'easeOut',
        times: l,
    } = {},
    c = void 0,
) {
    const u = { [e]: n };
    l && (u.offset = l);
    const h = Vi(a, i);
    Array.isArray(h) && (u.easing = h);
    const f = {
        delay: s,
        duration: i,
        easing: Array.isArray(h) ? 'linear' : h,
        fill: 'both',
        iterations: r + 1,
        direction: o === 'reverse' ? 'alternate' : 'normal',
    };
    return (c && (f.pseudoElement = c), t.animate(u, f));
}
function Ci(t) {
    return typeof t == 'function' && 'applyToOptions' in t;
}
function Yr({ type: t, ...e }) {
    return Ci(t) && Ai()
        ? t.applyToOptions(e)
        : (e.duration ?? (e.duration = 300), e.ease ?? (e.ease = 'easeOut'), e);
}
class Mi extends fn {
    constructor(e) {
        if (
            (super(),
            (this.finishedTime = null),
            (this.isStopped = !1),
            (this.manualStartTime = null),
            !e)
        )
            return;
        const {
            element: n,
            name: s,
            keyframes: i,
            pseudoElement: r,
            allowFlatten: o = !1,
            finalKeyframe: a,
            onComplete: l,
        } = e;
        ((this.isPseudoElement = !!r),
            (this.allowFlatten = o),
            (this.options = e),
            en(typeof e.type != 'string'));
        const c = Yr(e);
        ((this.animation = Xr(n, s, i, c, r)),
            c.autoplay === !1 && this.animation.pause(),
            (this.animation.onfinish = () => {
                if (((this.finishedTime = this.time), !r)) {
                    const u = hn(i, this.options, a, this.speed);
                    (this.updateMotionValue
                        ? this.updateMotionValue(u)
                        : zr(n, s, u),
                        this.animation.cancel());
                }
                (l?.(), this.notifyFinished());
            }));
    }
    play() {
        this.isStopped ||
            ((this.manualStartTime = null),
            this.animation.play(),
            this.state === 'finished' && this.updateFinished());
    }
    pause() {
        this.animation.pause();
    }
    complete() {
        this.animation.finish?.();
    }
    cancel() {
        try {
            this.animation.cancel();
        } catch {}
    }
    stop() {
        if (this.isStopped) return;
        this.isStopped = !0;
        const { state: e } = this;
        e === 'idle' ||
            e === 'finished' ||
            (this.updateMotionValue
                ? this.updateMotionValue()
                : this.commitStyles(),
            this.isPseudoElement || this.cancel());
    }
    commitStyles() {
        const e = this.options?.element;
        !this.isPseudoElement &&
            e?.isConnected &&
            this.animation.commitStyles?.();
    }
    get duration() {
        const e = this.animation.effect?.getComputedTiming?.().duration || 0;
        return K(Number(e));
    }
    get iterationDuration() {
        const { delay: e = 0 } = this.options || {};
        return this.duration + K(e);
    }
    get time() {
        return K(Number(this.animation.currentTime) || 0);
    }
    set time(e) {
        ((this.manualStartTime = null),
            (this.finishedTime = null),
            (this.animation.currentTime = _(e)));
    }
    get speed() {
        return this.animation.playbackRate;
    }
    set speed(e) {
        (e < 0 && (this.finishedTime = null),
            (this.animation.playbackRate = e));
    }
    get state() {
        return this.finishedTime !== null
            ? 'finished'
            : this.animation.playState;
    }
    get startTime() {
        return this.manualStartTime ?? Number(this.animation.startTime);
    }
    set startTime(e) {
        this.manualStartTime = this.animation.startTime = e;
    }
    attachTimeline({ timeline: e, observe: n }) {
        return (
            this.allowFlatten &&
                this.animation.effect?.updateTiming({ easing: 'linear' }),
            (this.animation.onfinish = null),
            e && _r() ? ((this.animation.timeline = e), $) : n(this)
        );
    }
}
const Di = { anticipate: ii, backInOut: si, circInOut: ri };
function qr(t) {
    return t in Di;
}
function Zr(t) {
    typeof t.ease == 'string' && qr(t.ease) && (t.ease = Di[t.ease]);
}
const ge = 10;
class Jr extends Mi {
    constructor(e) {
        (Zr(e),
            wi(e),
            super(e),
            e.startTime !== void 0 && (this.startTime = e.startTime),
            (this.options = e));
    }
    updateMotionValue(e) {
        const {
            motionValue: n,
            onUpdate: s,
            onComplete: i,
            element: r,
            ...o
        } = this.options;
        if (!n) return;
        if (e !== void 0) {
            n.set(e);
            return;
        }
        const a = new dn({ ...o, autoplay: !1 }),
            l = Math.max(ge, U.now() - this.startTime),
            c = Q(0, ge, l - ge);
        (n.setWithVelocity(
            a.sample(Math.max(0, l - c)).value,
            a.sample(l).value,
            c,
        ),
            a.stop());
    }
}
const Kn = (t, e) =>
    e === 'zIndex'
        ? !1
        : !!(
              typeof t == 'number' ||
              Array.isArray(t) ||
              (typeof t == 'string' &&
                  (X.test(t) || t === '0') &&
                  !t.startsWith('url('))
          );
function Qr(t) {
    const e = t[0];
    if (t.length === 1) return !0;
    for (let n = 0; n < t.length; n++) if (t[n] !== e) return !0;
}
function ta(t, e, n, s) {
    const i = t[0];
    if (i === null) return !1;
    if (e === 'display' || e === 'visibility') return !0;
    const r = t[t.length - 1],
        o = Kn(i, e),
        a = Kn(r, e);
    return !o || !a ? !1 : Qr(t) || ((n === 'spring' || Ci(n)) && s);
}
function Ne(t) {
    ((t.duration = 0), (t.type = 'keyframes'));
}
const ea = new Set(['opacity', 'clipPath', 'filter', 'transform']),
    na = Zs(() => Object.hasOwnProperty.call(Element.prototype, 'animate'));
function sa(t) {
    const {
        motionValue: e,
        name: n,
        repeatDelay: s,
        repeatType: i,
        damping: r,
        type: o,
    } = t;
    if (!(e?.owner?.current instanceof HTMLElement)) return !1;
    const { onUpdate: l, transformTemplate: c } = e.owner.getProps();
    return (
        na() &&
        n &&
        ea.has(n) &&
        (n !== 'transform' || !c) &&
        !l &&
        !s &&
        i !== 'mirror' &&
        r !== 0 &&
        o !== 'inertia'
    );
}
const ia = 40;
class oa extends fn {
    constructor({
        autoplay: e = !0,
        delay: n = 0,
        type: s = 'keyframes',
        repeat: i = 0,
        repeatDelay: r = 0,
        repeatType: o = 'loop',
        keyframes: a,
        name: l,
        motionValue: c,
        element: u,
        ...h
    }) {
        (super(),
            (this.stop = () => {
                (this._animation &&
                    (this._animation.stop(), this.stopTimeline?.()),
                    this.keyframeResolver?.cancel());
            }),
            (this.createdAt = U.now()));
        const f = {
                autoplay: e,
                delay: n,
                type: s,
                repeat: i,
                repeatDelay: r,
                repeatType: o,
                name: l,
                motionValue: c,
                element: u,
                ...h,
            },
            d = u?.KeyframeResolver || mn;
        ((this.keyframeResolver = new d(
            a,
            (m, y, p) => this.onKeyframesResolved(m, y, f, !p),
            l,
            c,
            u,
        )),
            this.keyframeResolver?.scheduleResolve());
    }
    onKeyframesResolved(e, n, s, i) {
        this.keyframeResolver = void 0;
        const {
            name: r,
            type: o,
            velocity: a,
            delay: l,
            isHandoff: c,
            onUpdate: u,
        } = s;
        ((this.resolvedAt = U.now()),
            ta(e, r, o, a) ||
                ((nt.instantAnimations || !l) && u?.(hn(e, s, n)),
                (e[0] = e[e.length - 1]),
                Ne(s),
                (s.repeat = 0)));
        const f = {
                startTime: i
                    ? this.resolvedAt
                        ? this.resolvedAt - this.createdAt > ia
                            ? this.resolvedAt
                            : this.createdAt
                        : this.createdAt
                    : void 0,
                finalKeyframe: n,
                ...s,
                keyframes: e,
            },
            d = !c && sa(f),
            m = f.motionValue?.owner?.current,
            y = d ? new Jr({ ...f, element: m }) : new dn(f);
        (y.finished
            .then(() => {
                this.notifyFinished();
            })
            .catch($),
            this.pendingTimeline &&
                ((this.stopTimeline = y.attachTimeline(this.pendingTimeline)),
                (this.pendingTimeline = void 0)),
            (this._animation = y));
    }
    get finished() {
        return this._animation ? this.animation.finished : this._finished;
    }
    then(e, n) {
        return this.finished.finally(e).then(() => {});
    }
    get animation() {
        return (
            this._animation || (this.keyframeResolver?.resume(), $r()),
            this._animation
        );
    }
    get duration() {
        return this.animation.duration;
    }
    get iterationDuration() {
        return this.animation.iterationDuration;
    }
    get time() {
        return this.animation.time;
    }
    set time(e) {
        this.animation.time = e;
    }
    get speed() {
        return this.animation.speed;
    }
    get state() {
        return this.animation.state;
    }
    set speed(e) {
        this.animation.speed = e;
    }
    get startTime() {
        return this.animation.startTime;
    }
    attachTimeline(e) {
        return (
            this._animation
                ? (this.stopTimeline = this.animation.attachTimeline(e))
                : (this.pendingTimeline = e),
            () => this.stop()
        );
    }
    play() {
        this.animation.play();
    }
    pause() {
        this.animation.pause();
    }
    complete() {
        this.animation.complete();
    }
    cancel() {
        (this._animation && this.animation.cancel(),
            this.keyframeResolver?.cancel());
    }
}
function Ei(t, e, n, s = 0, i = 1) {
    const r = Array.from(t)
            .sort((c, u) => c.sortNodePosition(u))
            .indexOf(e),
        o = t.size,
        a = (o - 1) * s;
    return typeof n == 'function' ? n(r, o) : i === 1 ? r * s : a - r * s;
}
const ra = /^var\(--(?:([\w-]+)|([\w-]+), ?([a-zA-Z\d ()%#.,-]+))\)/u;
function aa(t) {
    const e = ra.exec(t);
    if (!e) return [,];
    const [, n, s, i] = e;
    return [`--${n ?? s}`, i];
}
function Ri(t, e, n = 1) {
    const [s, i] = aa(t);
    if (!s) return;
    const r = window.getComputedStyle(e).getPropertyValue(s);
    if (r) {
        const o = r.trim();
        return Xs(o) ? parseFloat(o) : o;
    }
    return rn(i) ? Ri(i, e, n + 1) : i;
}
const la = { type: 'spring', stiffness: 500, damping: 25, restSpeed: 10 },
    ca = (t) => ({
        type: 'spring',
        stiffness: 550,
        damping: t === 0 ? 2 * Math.sqrt(550) : 30,
        restSpeed: 10,
    }),
    ua = { type: 'keyframes', duration: 0.8 },
    ha = { type: 'keyframes', ease: [0.25, 0.1, 0.35, 1], duration: 0.3 },
    fa = (t, { keyframes: e }) =>
        e.length > 2
            ? ua
            : bt.has(t)
              ? t.startsWith('scale')
                  ? ca(e[1])
                  : la
              : ha,
    da = (t) => t !== null;
function ma(t, { repeat: e, repeatType: n = 'loop' }, s) {
    const i = t.filter(da),
        r = e && n !== 'loop' && e % 2 === 1 ? 0 : i.length - 1;
    return i[r];
}
function Li(t, e) {
    if (t?.inherit && e) {
        const { inherit: n, ...s } = t;
        return { ...e, ...s };
    }
    return t;
}
function pn(t, e) {
    const n = t?.[e] ?? t?.default ?? t;
    return n !== t ? Li(n, t) : n;
}
function pa({
    when: t,
    delay: e,
    delayChildren: n,
    staggerChildren: s,
    staggerDirection: i,
    repeat: r,
    repeatType: o,
    repeatDelay: a,
    from: l,
    elapsed: c,
    ...u
}) {
    return !!Object.keys(u).length;
}
const gn =
    (t, e, n, s = {}, i, r) =>
    (o) => {
        const a = pn(s, t) || {},
            l = a.delay || s.delay || 0;
        let { elapsed: c = 0 } = s;
        c = c - _(l);
        const u = {
            keyframes: Array.isArray(n) ? n : [null, n],
            ease: 'easeOut',
            velocity: e.getVelocity(),
            ...a,
            delay: -c,
            onUpdate: (f) => {
                (e.set(f), a.onUpdate && a.onUpdate(f));
            },
            onComplete: () => {
                (o(), a.onComplete && a.onComplete());
            },
            name: t,
            motionValue: e,
            element: r ? void 0 : i,
        };
        (pa(a) || Object.assign(u, fa(t, u)),
            u.duration && (u.duration = _(u.duration)),
            u.repeatDelay && (u.repeatDelay = _(u.repeatDelay)),
            u.from !== void 0 && (u.keyframes[0] = u.from));
        let h = !1;
        if (
            ((u.type === !1 || (u.duration === 0 && !u.repeatDelay)) &&
                (Ne(u), u.delay === 0 && (h = !0)),
            (nt.instantAnimations ||
                nt.skipAnimations ||
                i?.shouldSkipAnimations) &&
                ((h = !0), Ne(u), (u.delay = 0)),
            (u.allowFlatten = !a.type && !a.ease),
            h && !r && e.get() !== void 0)
        ) {
            const f = ma(u.keyframes, a);
            if (f !== void 0) {
                D.update(() => {
                    (u.onUpdate(f), u.onComplete());
                });
                return;
            }
        }
        return a.isSync ? new dn(u) : new oa(u);
    };
function $n(t) {
    const e = [{}, {}];
    return (
        t?.values.forEach((n, s) => {
            ((e[0][s] = n.get()), (e[1][s] = n.getVelocity()));
        }),
        e
    );
}
function yn(t, e, n, s) {
    if (typeof e == 'function') {
        const [i, r] = $n(s);
        e = e(n !== void 0 ? n : t.custom, i, r);
    }
    if (
        (typeof e == 'string' && (e = t.variants && t.variants[e]),
        typeof e == 'function')
    ) {
        const [i, r] = $n(s);
        e = e(n !== void 0 ? n : t.custom, i, r);
    }
    return e;
}
function Tt(t, e, n) {
    const s = t.getProps();
    return yn(s, e, n !== void 0 ? n : s.custom, t);
}
const ki = new Set([
        'width',
        'height',
        'top',
        'left',
        'right',
        'bottom',
        ...St,
    ]),
    Hn = 30,
    ga = (t) => !isNaN(parseFloat(t));
class ya {
    constructor(e, n = {}) {
        ((this.canTrackVelocity = null),
            (this.events = {}),
            (this.updateAndNotify = (s) => {
                const i = U.now();
                if (
                    (this.updatedAt !== i && this.setPrevFrameValue(),
                    (this.prev = this.current),
                    this.setCurrent(s),
                    this.current !== this.prev &&
                        (this.events.change?.notify(this.current),
                        this.dependents))
                )
                    for (const r of this.dependents) r.dirty();
            }),
            (this.hasAnimated = !1),
            this.setCurrent(e),
            (this.owner = n.owner));
    }
    setCurrent(e) {
        ((this.current = e),
            (this.updatedAt = U.now()),
            this.canTrackVelocity === null &&
                e !== void 0 &&
                (this.canTrackVelocity = ga(this.current)));
    }
    setPrevFrameValue(e = this.current) {
        ((this.prevFrameValue = e), (this.prevUpdatedAt = this.updatedAt));
    }
    onChange(e) {
        return this.on('change', e);
    }
    on(e, n) {
        this.events[e] || (this.events[e] = new nn());
        const s = this.events[e].add(n);
        return e === 'change'
            ? () => {
                  (s(),
                      D.read(() => {
                          this.events.change.getSize() || this.stop();
                      }));
              }
            : s;
    }
    clearListeners() {
        for (const e in this.events) this.events[e].clear();
    }
    attach(e, n) {
        ((this.passiveEffect = e), (this.stopPassiveEffect = n));
    }
    set(e) {
        this.passiveEffect
            ? this.passiveEffect(e, this.updateAndNotify)
            : this.updateAndNotify(e);
    }
    setWithVelocity(e, n, s) {
        (this.set(n),
            (this.prev = void 0),
            (this.prevFrameValue = e),
            (this.prevUpdatedAt = this.updatedAt - s));
    }
    jump(e, n = !0) {
        (this.updateAndNotify(e),
            (this.prev = e),
            (this.prevUpdatedAt = this.prevFrameValue = void 0),
            n && this.stop(),
            this.stopPassiveEffect && this.stopPassiveEffect());
    }
    dirty() {
        this.events.change?.notify(this.current);
    }
    addDependent(e) {
        (this.dependents || (this.dependents = new Set()),
            this.dependents.add(e));
    }
    removeDependent(e) {
        this.dependents && this.dependents.delete(e);
    }
    get() {
        return this.current;
    }
    getPrevious() {
        return this.prev;
    }
    getVelocity() {
        const e = U.now();
        if (
            !this.canTrackVelocity ||
            this.prevFrameValue === void 0 ||
            e - this.updatedAt > Hn
        )
            return 0;
        const n = Math.min(this.updatedAt - this.prevUpdatedAt, Hn);
        return Js(
            parseFloat(this.current) - parseFloat(this.prevFrameValue),
            n,
        );
    }
    start(e) {
        return (
            this.stop(),
            new Promise((n) => {
                ((this.hasAnimated = !0),
                    (this.animation = e(n)),
                    this.events.animationStart &&
                        this.events.animationStart.notify());
            }).then(() => {
                (this.events.animationComplete &&
                    this.events.animationComplete.notify(),
                    this.clearAnimation());
            })
        );
    }
    stop() {
        (this.animation &&
            (this.animation.stop(),
            this.events.animationCancel &&
                this.events.animationCancel.notify()),
            this.clearAnimation());
    }
    isAnimating() {
        return !!this.animation;
    }
    clearAnimation() {
        delete this.animation;
    }
    destroy() {
        (this.dependents?.clear(),
            this.events.destroy?.notify(),
            this.clearListeners(),
            this.stop(),
            this.stopPassiveEffect && this.stopPassiveEffect());
    }
}
function wt(t, e) {
    return new ya(t, e);
}
const Ue = (t) => Array.isArray(t);
function va(t, e, n) {
    t.hasValue(e) ? t.getValue(e).set(n) : t.addValue(e, wt(n));
}
function xa(t) {
    return Ue(t) ? t[t.length - 1] || 0 : t;
}
function Ta(t, e) {
    const n = Tt(t, e);
    let { transitionEnd: s = {}, transition: i = {}, ...r } = n || {};
    r = { ...r, ...s };
    for (const o in r) {
        const a = xa(r[o]);
        va(t, o, a);
    }
}
const O = (t) => !!(t && t.getVelocity);
function wa(t) {
    return !!(O(t) && t.add);
}
function We(t, e) {
    const n = t.getValue('willChange');
    if (wa(n)) return n.add(e);
    if (!n && nt.WillChange) {
        const s = new nt.WillChange('auto');
        (t.addValue('willChange', s), s.add(e));
    }
}
function vn(t) {
    return t.replace(/([A-Z])/g, (e) => `-${e.toLowerCase()}`);
}
const Pa = 'framerAppearId',
    Ii = 'data-' + vn(Pa);
function Fi(t) {
    return t.props[Ii];
}
function Sa({ protectedKeys: t, needsAnimating: e }, n) {
    const s = t.hasOwnProperty(n) && e[n] !== !0;
    return ((e[n] = !1), s);
}
function ji(t, e, { delay: n = 0, transitionOverride: s, type: i } = {}) {
    let { transition: r, transitionEnd: o, ...a } = e;
    const l = t.getDefaultTransition();
    r = r ? Li(r, l) : l;
    const c = r?.reduceMotion;
    s && (r = s);
    const u = [],
        h = i && t.animationState && t.animationState.getState()[i];
    for (const f in a) {
        const d = t.getValue(f, t.latestValues[f] ?? null),
            m = a[f];
        if (m === void 0 || (h && Sa(h, f))) continue;
        const y = { delay: n, ...pn(r || {}, f) },
            p = d.get();
        if (
            p !== void 0 &&
            !d.isAnimating &&
            !Array.isArray(m) &&
            m === p &&
            !y.velocity
        )
            continue;
        let g = !1;
        if (window.MotionHandoffAnimation) {
            const S = Fi(t);
            if (S) {
                const w = window.MotionHandoffAnimation(S, f, D);
                w !== null && ((y.startTime = w), (g = !0));
            }
        }
        We(t, f);
        const x = c ?? t.shouldReduceMotion;
        d.start(gn(f, d, m, x && ki.has(f) ? { type: !1 } : y, t, g));
        const v = d.animation;
        v && u.push(v);
    }
    if (o) {
        const f = () =>
            D.update(() => {
                o && Ta(t, o);
            });
        u.length ? Promise.all(u).then(f) : f();
    }
    return u;
}
function Ke(t, e, n = {}) {
    const s = Tt(t, e, n.type === 'exit' ? t.presenceContext?.custom : void 0);
    let { transition: i = t.getDefaultTransition() || {} } = s || {};
    n.transitionOverride && (i = n.transitionOverride);
    const r = s ? () => Promise.all(ji(t, s, n)) : () => Promise.resolve(),
        o =
            t.variantChildren && t.variantChildren.size
                ? (l = 0) => {
                      const {
                          delayChildren: c = 0,
                          staggerChildren: u,
                          staggerDirection: h,
                      } = i;
                      return ba(t, e, l, c, u, h, n);
                  }
                : () => Promise.resolve(),
        { when: a } = i;
    if (a) {
        const [l, c] = a === 'beforeChildren' ? [r, o] : [o, r];
        return l().then(() => c());
    } else return Promise.all([r(), o(n.delay)]);
}
function ba(t, e, n = 0, s = 0, i = 0, r = 1, o) {
    const a = [];
    for (const l of t.variantChildren)
        (l.notify('AnimationStart', e),
            a.push(
                Ke(l, e, {
                    ...o,
                    delay:
                        n +
                        (typeof s == 'function' ? 0 : s) +
                        Ei(t.variantChildren, l, s, i, r),
                }).then(() => l.notify('AnimationComplete', e)),
            ));
    return Promise.all(a);
}
function Aa(t, e, n = {}) {
    t.notify('AnimationStart', e);
    let s;
    if (Array.isArray(e)) {
        const i = e.map((r) => Ke(t, r, n));
        s = Promise.all(i);
    } else if (typeof e == 'string') s = Ke(t, e, n);
    else {
        const i = typeof e == 'function' ? Tt(t, e, n.custom) : e;
        s = Promise.all(ji(t, i, n));
    }
    return s.then(() => {
        t.notify('AnimationComplete', e);
    });
}
const Va = { test: (t) => t === 'auto', parse: (t) => t },
    Bi = (t) => (e) => e.test(t),
    Oi = [Pt, P, J, it, er, tr, Va],
    zn = (t) => Oi.find(Bi(t));
function Ca(t) {
    return typeof t == 'number'
        ? t === 0
        : t !== null
          ? t === 'none' || t === '0' || qs(t)
          : !0;
}
const Ma = new Set(['brightness', 'contrast', 'saturate', 'opacity']);
function Da(t) {
    const [e, n] = t.slice(0, -1).split('(');
    if (e === 'drop-shadow') return t;
    const [s] = n.match(an) || [];
    if (!s) return t;
    const i = n.replace(s, '');
    let r = Ma.has(e) ? 1 : 0;
    return (s !== n && (r *= 100), e + '(' + r + i + ')');
}
const Ea = /\b([a-z-]*)\(.*?\)/gu,
    $e = {
        ...X,
        getAnimatableNone: (t) => {
            const e = t.match(Ea);
            return e ? e.map(Da).join(' ') : t;
        },
    },
    He = {
        ...X,
        getAnimatableNone: (t) => {
            const e = X.parse(t);
            return X.createTransformer(t)(
                e.map((s) =>
                    typeof s == 'number'
                        ? 0
                        : typeof s == 'object'
                          ? { ...s, alpha: 1 }
                          : s,
                ),
            );
        },
    },
    Gn = { ...Pt, transform: Math.round },
    Ra = {
        rotate: it,
        rotateX: it,
        rotateY: it,
        rotateZ: it,
        scale: Wt,
        scaleX: Wt,
        scaleY: Wt,
        scaleZ: Wt,
        skew: it,
        skewX: it,
        skewY: it,
        distance: P,
        translateX: P,
        translateY: P,
        translateZ: P,
        x: P,
        y: P,
        z: P,
        perspective: P,
        transformPerspective: P,
        opacity: Lt,
        originX: Ln,
        originY: Ln,
        originZ: P,
    },
    xn = {
        borderWidth: P,
        borderTopWidth: P,
        borderRightWidth: P,
        borderBottomWidth: P,
        borderLeftWidth: P,
        borderRadius: P,
        borderTopLeftRadius: P,
        borderTopRightRadius: P,
        borderBottomRightRadius: P,
        borderBottomLeftRadius: P,
        width: P,
        maxWidth: P,
        height: P,
        maxHeight: P,
        top: P,
        right: P,
        bottom: P,
        left: P,
        inset: P,
        insetBlock: P,
        insetBlockStart: P,
        insetBlockEnd: P,
        insetInline: P,
        insetInlineStart: P,
        insetInlineEnd: P,
        padding: P,
        paddingTop: P,
        paddingRight: P,
        paddingBottom: P,
        paddingLeft: P,
        paddingBlock: P,
        paddingBlockStart: P,
        paddingBlockEnd: P,
        paddingInline: P,
        paddingInlineStart: P,
        paddingInlineEnd: P,
        margin: P,
        marginTop: P,
        marginRight: P,
        marginBottom: P,
        marginLeft: P,
        marginBlock: P,
        marginBlockStart: P,
        marginBlockEnd: P,
        marginInline: P,
        marginInlineStart: P,
        marginInlineEnd: P,
        fontSize: P,
        backgroundPositionX: P,
        backgroundPositionY: P,
        ...Ra,
        zIndex: Gn,
        fillOpacity: Lt,
        strokeOpacity: Lt,
        numOctaves: Gn,
    },
    La = {
        ...xn,
        color: I,
        backgroundColor: I,
        outlineColor: I,
        fill: I,
        stroke: I,
        borderColor: I,
        borderTopColor: I,
        borderRightColor: I,
        borderBottomColor: I,
        borderLeftColor: I,
        filter: $e,
        WebkitFilter: $e,
        mask: He,
        WebkitMask: He,
    },
    Ni = (t) => La[t],
    ka = new Set([$e, He]);
function Ui(t, e) {
    let n = Ni(t);
    return (
        ka.has(n) || (n = X),
        n.getAnimatableNone ? n.getAnimatableNone(e) : void 0
    );
}
const Ia = new Set(['auto', 'none', '0']);
function Fa(t, e, n) {
    let s = 0,
        i;
    for (; s < t.length && !i; ) {
        const r = t[s];
        (typeof r == 'string' &&
            !Ia.has(r) &&
            kt(r).values.length &&
            (i = t[s]),
            s++);
    }
    if (i && n) for (const r of e) t[r] = Ui(n, i);
}
class ja extends mn {
    constructor(e, n, s, i, r) {
        super(e, n, s, i, r, !0);
    }
    readKeyframes() {
        const { unresolvedKeyframes: e, element: n, name: s } = this;
        if (!n || !n.current) return;
        super.readKeyframes();
        for (let u = 0; u < e.length; u++) {
            let h = e[u];
            if (typeof h == 'string' && ((h = h.trim()), rn(h))) {
                const f = Ri(h, n.current);
                (f !== void 0 && (e[u] = f),
                    u === e.length - 1 && (this.finalKeyframe = h));
            }
        }
        if ((this.resolveNoneKeyframes(), !ki.has(s) || e.length !== 2)) return;
        const [i, r] = e,
            o = zn(i),
            a = zn(r),
            l = Rn(i),
            c = Rn(r);
        if (l !== c && ot[s]) {
            this.needsMeasurement = !0;
            return;
        }
        if (o !== a)
            if (Un(o) && Un(a))
                for (let u = 0; u < e.length; u++) {
                    const h = e[u];
                    typeof h == 'string' && (e[u] = parseFloat(h));
                }
            else ot[s] && (this.needsMeasurement = !0);
    }
    resolveNoneKeyframes() {
        const { unresolvedKeyframes: e, name: n } = this,
            s = [];
        for (let i = 0; i < e.length; i++)
            (e[i] === null || Ca(e[i])) && s.push(i);
        s.length && Fa(e, s, n);
    }
    measureInitialState() {
        const { element: e, unresolvedKeyframes: n, name: s } = this;
        if (!e || !e.current) return;
        (s === 'height' && (this.suspendedScrollY = window.pageYOffset),
            (this.measuredOrigin = ot[s](
                e.measureViewportBox(),
                window.getComputedStyle(e.current),
            )),
            (n[0] = this.measuredOrigin));
        const i = n[n.length - 1];
        i !== void 0 && e.getValue(s, i).jump(i, !1);
    }
    measureEndState() {
        const { element: e, name: n, unresolvedKeyframes: s } = this;
        if (!e || !e.current) return;
        const i = e.getValue(n);
        i && i.jump(this.measuredOrigin, !1);
        const r = s.length - 1,
            o = s[r];
        ((s[r] = ot[n](
            e.measureViewportBox(),
            window.getComputedStyle(e.current),
        )),
            o !== null &&
                this.finalKeyframe === void 0 &&
                (this.finalKeyframe = o),
            this.removedTransforms?.length &&
                this.removedTransforms.forEach(([a, l]) => {
                    e.getValue(a).set(l);
                }),
            this.resolveNoneKeyframes());
    }
}
const Ba = new Set(['opacity', 'clipPath', 'filter', 'transform']);
function Wi(t, e, n) {
    if (t == null) return [];
    if (t instanceof EventTarget) return [t];
    if (typeof t == 'string') {
        let s = document;
        const i = n?.[t] ?? s.querySelectorAll(t);
        return i ? Array.from(i) : [];
    }
    return Array.from(t).filter((s) => s != null);
}
const Ki = (t, e) => (e && typeof t == 'number' ? e.transform(t) : t);
function ze(t) {
    return Ys(t) && 'offsetHeight' in t;
}
const { schedule: Tn } = ci(queueMicrotask, !1),
    G = { x: !1, y: !1 };
function $i() {
    return G.x || G.y;
}
function Oa(t) {
    return t === 'x' || t === 'y'
        ? G[t]
            ? null
            : ((G[t] = !0),
              () => {
                  G[t] = !1;
              })
        : G.x || G.y
          ? null
          : ((G.x = G.y = !0),
            () => {
                G.x = G.y = !1;
            });
}
function Hi(t, e) {
    const n = Wi(t),
        s = new AbortController(),
        i = { passive: !0, ...e, signal: s.signal };
    return [n, i, () => s.abort()];
}
function Na(t) {
    return !(t.pointerType === 'touch' || $i());
}
function Ua(t, e, n = {}) {
    const [s, i, r] = Hi(t, n);
    return (
        s.forEach((o) => {
            let a = !1,
                l = !1,
                c;
            const u = () => {
                    o.removeEventListener('pointerleave', m);
                },
                h = (p) => {
                    (c && (c(p), (c = void 0)), u());
                },
                f = (p) => {
                    ((a = !1),
                        window.removeEventListener('pointerup', f),
                        window.removeEventListener('pointercancel', f),
                        l && ((l = !1), h(p)));
                },
                d = () => {
                    ((a = !0),
                        window.addEventListener('pointerup', f, i),
                        window.addEventListener('pointercancel', f, i));
                },
                m = (p) => {
                    if (p.pointerType !== 'touch') {
                        if (a) {
                            l = !0;
                            return;
                        }
                        h(p);
                    }
                },
                y = (p) => {
                    if (!Na(p)) return;
                    l = !1;
                    const g = e(o, p);
                    typeof g == 'function' &&
                        ((c = g), o.addEventListener('pointerleave', m, i));
                };
            (o.addEventListener('pointerenter', y, i),
                o.addEventListener('pointerdown', d, i));
        }),
        r
    );
}
const zi = (t, e) => (e ? (t === e ? !0 : zi(t, e.parentElement)) : !1),
    wn = (t) =>
        t.pointerType === 'mouse'
            ? typeof t.button != 'number' || t.button <= 0
            : t.isPrimary !== !1,
    Wa = new Set(['BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'A']);
function Ka(t) {
    return Wa.has(t.tagName) || t.isContentEditable === !0;
}
const $a = new Set(['INPUT', 'SELECT', 'TEXTAREA']);
function Ha(t) {
    return $a.has(t.tagName) || t.isContentEditable === !0;
}
const Gt = new WeakSet();
function _n(t) {
    return (e) => {
        e.key === 'Enter' && t(e);
    };
}
function ye(t, e) {
    t.dispatchEvent(
        new PointerEvent('pointer' + e, { isPrimary: !0, bubbles: !0 }),
    );
}
const za = (t, e) => {
    const n = t.currentTarget;
    if (!n) return;
    const s = _n(() => {
        if (Gt.has(n)) return;
        ye(n, 'down');
        const i = _n(() => {
                ye(n, 'up');
            }),
            r = () => ye(n, 'cancel');
        (n.addEventListener('keyup', i, e), n.addEventListener('blur', r, e));
    });
    (n.addEventListener('keydown', s, e),
        n.addEventListener(
            'blur',
            () => n.removeEventListener('keydown', s),
            e,
        ));
};
function Xn(t) {
    return wn(t) && !$i();
}
const Yn = new WeakSet();
function Ga(t, e, n = {}) {
    const [s, i, r] = Hi(t, n),
        o = (a) => {
            const l = a.currentTarget;
            if (!Xn(a) || Yn.has(a)) return;
            (Gt.add(l), n.stopPropagation && Yn.add(a));
            const c = e(l, a),
                u = (d, m) => {
                    (window.removeEventListener('pointerup', h),
                        window.removeEventListener('pointercancel', f),
                        Gt.has(l) && Gt.delete(l),
                        Xn(d) &&
                            typeof c == 'function' &&
                            c(d, { success: m }));
                },
                h = (d) => {
                    u(
                        d,
                        l === window ||
                            l === document ||
                            n.useGlobalTarget ||
                            zi(l, d.target),
                    );
                },
                f = (d) => {
                    u(d, !1);
                };
            (window.addEventListener('pointerup', h, i),
                window.addEventListener('pointercancel', f, i));
        };
    return (
        s.forEach((a) => {
            ((n.useGlobalTarget ? window : a).addEventListener(
                'pointerdown',
                o,
                i,
            ),
                ze(a) &&
                    (a.addEventListener('focus', (c) => za(c, i)),
                    !Ka(a) && !a.hasAttribute('tabindex') && (a.tabIndex = 0)));
        }),
        r
    );
}
function Pn(t) {
    return Ys(t) && 'ownerSVGElement' in t;
}
const _t = new WeakMap();
let Xt;
const Gi = (t, e, n) => (s, i) =>
        i && i[0]
            ? i[0][t + 'Size']
            : Pn(s) && 'getBBox' in s
              ? s.getBBox()[e]
              : s[n],
    _a = Gi('inline', 'width', 'offsetWidth'),
    Xa = Gi('block', 'height', 'offsetHeight');
function Ya({ target: t, borderBoxSize: e }) {
    _t.get(t)?.forEach((n) => {
        n(t, {
            get width() {
                return _a(t, e);
            },
            get height() {
                return Xa(t, e);
            },
        });
    });
}
function qa(t) {
    t.forEach(Ya);
}
function Za() {
    typeof ResizeObserver > 'u' || (Xt = new ResizeObserver(qa));
}
function Ja(t, e) {
    Xt || Za();
    const n = Wi(t);
    return (
        n.forEach((s) => {
            let i = _t.get(s);
            (i || ((i = new Set()), _t.set(s, i)), i.add(e), Xt?.observe(s));
        }),
        () => {
            n.forEach((s) => {
                const i = _t.get(s);
                (i?.delete(e), i?.size || Xt?.unobserve(s));
            });
        }
    );
}
const Yt = new Set();
let gt;
function Qa() {
    ((gt = () => {
        const t = {
            get width() {
                return window.innerWidth;
            },
            get height() {
                return window.innerHeight;
            },
        };
        Yt.forEach((e) => e(t));
    }),
        window.addEventListener('resize', gt));
}
function tl(t) {
    return (
        Yt.add(t),
        gt || Qa(),
        () => {
            (Yt.delete(t),
                !Yt.size &&
                    typeof gt == 'function' &&
                    (window.removeEventListener('resize', gt), (gt = void 0)));
        }
    );
}
function qn(t, e) {
    return typeof t == 'function' ? tl(t) : Ja(t, e);
}
function el(t) {
    return Pn(t) && t.tagName === 'svg';
}
const nl = [...Oi, I, X],
    sl = (t) => nl.find(Bi(t)),
    Zn = () => ({ translate: 0, scale: 1, origin: 0, originPoint: 0 }),
    yt = () => ({ x: Zn(), y: Zn() }),
    Jn = () => ({ min: 0, max: 0 }),
    F = () => ({ x: Jn(), y: Jn() }),
    il = new WeakMap();
function le(t) {
    return t !== null && typeof t == 'object' && typeof t.start == 'function';
}
function It(t) {
    return typeof t == 'string' || Array.isArray(t);
}
const Sn = [
        'animate',
        'whileInView',
        'whileFocus',
        'whileHover',
        'whileTap',
        'whileDrag',
        'exit',
    ],
    bn = ['initial', ...Sn];
function ce(t) {
    return le(t.animate) || bn.some((e) => It(t[e]));
}
function _i(t) {
    return !!(ce(t) || t.variants);
}
function ol(t, e, n) {
    for (const s in e) {
        const i = e[s],
            r = n[s];
        if (O(i)) t.addValue(s, i);
        else if (O(r)) t.addValue(s, wt(i, { owner: t }));
        else if (r !== i)
            if (t.hasValue(s)) {
                const o = t.getValue(s);
                o.liveStyle === !0 ? o.jump(i) : o.hasAnimated || o.set(i);
            } else {
                const o = t.getStaticValue(s);
                t.addValue(s, wt(o !== void 0 ? o : i, { owner: t }));
            }
    }
    for (const s in n) e[s] === void 0 && t.removeValue(s);
    return e;
}
const Ge = { current: null },
    Xi = { current: !1 },
    rl = typeof window < 'u';
function al() {
    if (((Xi.current = !0), !!rl))
        if (window.matchMedia) {
            const t = window.matchMedia('(prefers-reduced-motion)'),
                e = () => (Ge.current = t.matches);
            (t.addEventListener('change', e), e());
        } else Ge.current = !1;
}
const Qn = [
    'AnimationStart',
    'AnimationComplete',
    'Update',
    'BeforeLayoutMeasure',
    'LayoutMeasure',
    'LayoutAnimationStart',
    'LayoutAnimationComplete',
];
let se = {};
function Yi(t) {
    se = t;
}
function ll() {
    return se;
}
class cl {
    scrapeMotionValuesFromProps(e, n, s) {
        return {};
    }
    constructor(
        {
            parent: e,
            props: n,
            presenceContext: s,
            reducedMotionConfig: i,
            skipAnimations: r,
            blockInitialAnimation: o,
            visualState: a,
        },
        l = {},
    ) {
        ((this.current = null),
            (this.children = new Set()),
            (this.isVariantNode = !1),
            (this.isControllingVariants = !1),
            (this.shouldReduceMotion = null),
            (this.shouldSkipAnimations = !1),
            (this.values = new Map()),
            (this.KeyframeResolver = mn),
            (this.features = {}),
            (this.valueSubscriptions = new Map()),
            (this.prevMotionValues = {}),
            (this.hasBeenMounted = !1),
            (this.events = {}),
            (this.propEventSubscriptions = {}),
            (this.notifyUpdate = () =>
                this.notify('Update', this.latestValues)),
            (this.render = () => {
                this.current &&
                    (this.triggerBuild(),
                    this.renderInstance(
                        this.current,
                        this.renderState,
                        this.props.style,
                        this.projection,
                    ));
            }),
            (this.renderScheduledAt = 0),
            (this.scheduleRender = () => {
                const d = U.now();
                this.renderScheduledAt < d &&
                    ((this.renderScheduledAt = d),
                    D.render(this.render, !1, !0));
            }));
        const { latestValues: c, renderState: u } = a;
        ((this.latestValues = c),
            (this.baseTarget = { ...c }),
            (this.initialValues = n.initial ? { ...c } : {}),
            (this.renderState = u),
            (this.parent = e),
            (this.props = n),
            (this.presenceContext = s),
            (this.depth = e ? e.depth + 1 : 0),
            (this.reducedMotionConfig = i),
            (this.skipAnimationsConfig = r),
            (this.options = l),
            (this.blockInitialAnimation = !!o),
            (this.isControllingVariants = ce(n)),
            (this.isVariantNode = _i(n)),
            this.isVariantNode && (this.variantChildren = new Set()),
            (this.manuallyAnimateOnMount = !!(e && e.current)));
        const { willChange: h, ...f } = this.scrapeMotionValuesFromProps(
            n,
            {},
            this,
        );
        for (const d in f) {
            const m = f[d];
            c[d] !== void 0 && O(m) && m.set(c[d]);
        }
    }
    mount(e) {
        if (this.hasBeenMounted)
            for (const n in this.initialValues)
                (this.values.get(n)?.jump(this.initialValues[n]),
                    (this.latestValues[n] = this.initialValues[n]));
        ((this.current = e),
            il.set(e, this),
            this.projection &&
                !this.projection.instance &&
                this.projection.mount(e),
            this.parent &&
                this.isVariantNode &&
                !this.isControllingVariants &&
                (this.removeFromVariantTree =
                    this.parent.addVariantChild(this)),
            this.values.forEach((n, s) => this.bindToMotionValue(s, n)),
            this.reducedMotionConfig === 'never'
                ? (this.shouldReduceMotion = !1)
                : this.reducedMotionConfig === 'always'
                  ? (this.shouldReduceMotion = !0)
                  : (Xi.current || al(),
                    (this.shouldReduceMotion = Ge.current)),
            (this.shouldSkipAnimations = this.skipAnimationsConfig ?? !1),
            this.parent?.addChild(this),
            this.update(this.props, this.presenceContext),
            (this.hasBeenMounted = !0));
    }
    unmount() {
        (this.projection && this.projection.unmount(),
            rt(this.notifyUpdate),
            rt(this.render),
            this.valueSubscriptions.forEach((e) => e()),
            this.valueSubscriptions.clear(),
            this.removeFromVariantTree && this.removeFromVariantTree(),
            this.parent?.removeChild(this));
        for (const e in this.events) this.events[e].clear();
        for (const e in this.features) {
            const n = this.features[e];
            n && (n.unmount(), (n.isMounted = !1));
        }
        this.current = null;
    }
    addChild(e) {
        (this.children.add(e),
            this.enteringChildren ?? (this.enteringChildren = new Set()),
            this.enteringChildren.add(e));
    }
    removeChild(e) {
        (this.children.delete(e),
            this.enteringChildren && this.enteringChildren.delete(e));
    }
    bindToMotionValue(e, n) {
        if (
            (this.valueSubscriptions.has(e) && this.valueSubscriptions.get(e)(),
            n.accelerate && Ba.has(e) && this.current instanceof HTMLElement)
        ) {
            const {
                    factory: o,
                    keyframes: a,
                    times: l,
                    ease: c,
                    duration: u,
                } = n.accelerate,
                h = new Mi({
                    element: this.current,
                    name: e,
                    keyframes: a,
                    times: l,
                    ease: c,
                    duration: _(u),
                }),
                f = o(h);
            this.valueSubscriptions.set(e, () => {
                (f(), h.cancel());
            });
            return;
        }
        const s = bt.has(e);
        s && this.onBindTransform && this.onBindTransform();
        const i = n.on('change', (o) => {
            ((this.latestValues[e] = o),
                this.props.onUpdate && D.preRender(this.notifyUpdate),
                s && this.projection && (this.projection.isTransformDirty = !0),
                this.scheduleRender());
        });
        let r;
        (typeof window < 'u' &&
            window.MotionCheckAppearSync &&
            (r = window.MotionCheckAppearSync(this, e, n)),
            this.valueSubscriptions.set(e, () => {
                (i(), r && r(), n.owner && n.stop());
            }));
    }
    sortNodePosition(e) {
        return !this.current ||
            !this.sortInstanceNodePosition ||
            this.type !== e.type
            ? 0
            : this.sortInstanceNodePosition(this.current, e.current);
    }
    updateFeatures() {
        let e = 'animation';
        for (e in se) {
            const n = se[e];
            if (!n) continue;
            const { isEnabled: s, Feature: i } = n;
            if (
                (!this.features[e] &&
                    i &&
                    s(this.props) &&
                    (this.features[e] = new i(this)),
                this.features[e])
            ) {
                const r = this.features[e];
                r.isMounted ? r.update() : (r.mount(), (r.isMounted = !0));
            }
        }
    }
    triggerBuild() {
        this.build(this.renderState, this.latestValues, this.props);
    }
    measureViewportBox() {
        return this.current
            ? this.measureInstanceViewportBox(this.current, this.props)
            : F();
    }
    getStaticValue(e) {
        return this.latestValues[e];
    }
    setStaticValue(e, n) {
        this.latestValues[e] = n;
    }
    update(e, n) {
        ((e.transformTemplate || this.props.transformTemplate) &&
            this.scheduleRender(),
            (this.prevProps = this.props),
            (this.props = e),
            (this.prevPresenceContext = this.presenceContext),
            (this.presenceContext = n));
        for (let s = 0; s < Qn.length; s++) {
            const i = Qn[s];
            this.propEventSubscriptions[i] &&
                (this.propEventSubscriptions[i](),
                delete this.propEventSubscriptions[i]);
            const r = 'on' + i,
                o = e[r];
            o && (this.propEventSubscriptions[i] = this.on(i, o));
        }
        ((this.prevMotionValues = ol(
            this,
            this.scrapeMotionValuesFromProps(e, this.prevProps || {}, this),
            this.prevMotionValues,
        )),
            this.handleChildMotionValue && this.handleChildMotionValue());
    }
    getProps() {
        return this.props;
    }
    getVariant(e) {
        return this.props.variants ? this.props.variants[e] : void 0;
    }
    getDefaultTransition() {
        return this.props.transition;
    }
    getTransformPagePoint() {
        return this.props.transformPagePoint;
    }
    getClosestVariantNode() {
        return this.isVariantNode
            ? this
            : this.parent
              ? this.parent.getClosestVariantNode()
              : void 0;
    }
    addVariantChild(e) {
        const n = this.getClosestVariantNode();
        if (n)
            return (
                n.variantChildren && n.variantChildren.add(e),
                () => n.variantChildren.delete(e)
            );
    }
    addValue(e, n) {
        const s = this.values.get(e);
        n !== s &&
            (s && this.removeValue(e),
            this.bindToMotionValue(e, n),
            this.values.set(e, n),
            (this.latestValues[e] = n.get()));
    }
    removeValue(e) {
        this.values.delete(e);
        const n = this.valueSubscriptions.get(e);
        (n && (n(), this.valueSubscriptions.delete(e)),
            delete this.latestValues[e],
            this.removeValueFromRenderState(e, this.renderState));
    }
    hasValue(e) {
        return this.values.has(e);
    }
    getValue(e, n) {
        if (this.props.values && this.props.values[e])
            return this.props.values[e];
        let s = this.values.get(e);
        return (
            s === void 0 &&
                n !== void 0 &&
                ((s = wt(n === null ? void 0 : n, { owner: this })),
                this.addValue(e, s)),
            s
        );
    }
    readValue(e, n) {
        let s =
            this.latestValues[e] !== void 0 || !this.current
                ? this.latestValues[e]
                : (this.getBaseTargetFromProps(this.props, e) ??
                  this.readValueFromInstance(this.current, e, this.options));
        return (
            s != null &&
                (typeof s == 'string' && (Xs(s) || qs(s))
                    ? (s = parseFloat(s))
                    : !sl(s) && X.test(n) && (s = Ui(e, n)),
                this.setBaseTarget(e, O(s) ? s.get() : s)),
            O(s) ? s.get() : s
        );
    }
    setBaseTarget(e, n) {
        this.baseTarget[e] = n;
    }
    getBaseTarget(e) {
        const { initial: n } = this.props;
        let s;
        if (typeof n == 'string' || typeof n == 'object') {
            const r = yn(this.props, n, this.presenceContext?.custom);
            r && (s = r[e]);
        }
        if (n && s !== void 0) return s;
        const i = this.getBaseTargetFromProps(this.props, e);
        return i !== void 0 && !O(i)
            ? i
            : this.initialValues[e] !== void 0 && s === void 0
              ? void 0
              : this.baseTarget[e];
    }
    on(e, n) {
        return (
            this.events[e] || (this.events[e] = new nn()),
            this.events[e].add(n)
        );
    }
    notify(e, ...n) {
        this.events[e] && this.events[e].notify(...n);
    }
    scheduleRenderMicrotask() {
        Tn.render(this.render);
    }
}
class qi extends cl {
    constructor() {
        (super(...arguments), (this.KeyframeResolver = ja));
    }
    sortInstanceNodePosition(e, n) {
        return e.compareDocumentPosition(n) & 2 ? 1 : -1;
    }
    getBaseTargetFromProps(e, n) {
        const s = e.style;
        return s ? s[n] : void 0;
    }
    removeValueFromRenderState(e, { vars: n, style: s }) {
        (delete n[e], delete s[e]);
    }
    handleChildMotionValue() {
        this.childSubscription &&
            (this.childSubscription(), delete this.childSubscription);
        const { children: e } = this.props;
        O(e) &&
            (this.childSubscription = e.on('change', (n) => {
                this.current && (this.current.textContent = `${n}`);
            }));
    }
}
class at {
    constructor(e) {
        ((this.isMounted = !1), (this.node = e));
    }
    update() {}
}
function Zi({ top: t, left: e, right: n, bottom: s }) {
    return { x: { min: e, max: n }, y: { min: t, max: s } };
}
function ul({ x: t, y: e }) {
    return { top: e.min, right: t.max, bottom: e.max, left: t.min };
}
function hl(t, e) {
    if (!e) return t;
    const n = e({ x: t.left, y: t.top }),
        s = e({ x: t.right, y: t.bottom });
    return { top: n.y, left: n.x, bottom: s.y, right: s.x };
}
function ve(t) {
    return t === void 0 || t === 1;
}
function _e({ scale: t, scaleX: e, scaleY: n }) {
    return !ve(t) || !ve(e) || !ve(n);
}
function ut(t) {
    return (
        _e(t) ||
        Ji(t) ||
        t.z ||
        t.rotate ||
        t.rotateX ||
        t.rotateY ||
        t.skewX ||
        t.skewY
    );
}
function Ji(t) {
    return ts(t.x) || ts(t.y);
}
function ts(t) {
    return t && t !== '0%';
}
function ie(t, e, n) {
    const s = t - n,
        i = e * s;
    return n + i;
}
function es(t, e, n, s, i) {
    return (i !== void 0 && (t = ie(t, i, s)), ie(t, n, s) + e);
}
function Xe(t, e = 0, n = 1, s, i) {
    ((t.min = es(t.min, e, n, s, i)), (t.max = es(t.max, e, n, s, i)));
}
function Qi(t, { x: e, y: n }) {
    (Xe(t.x, e.translate, e.scale, e.originPoint),
        Xe(t.y, n.translate, n.scale, n.originPoint));
}
const ns = 0.999999999999,
    ss = 1.0000000000001;
function fl(t, e, n, s = !1) {
    const i = n.length;
    if (!i) return;
    e.x = e.y = 1;
    let r, o;
    for (let a = 0; a < i; a++) {
        ((r = n[a]), (o = r.projectionDelta));
        const { visualElement: l } = r.options;
        (l && l.props.style && l.props.style.display === 'contents') ||
            (s &&
                r.options.layoutScroll &&
                r.scroll &&
                r !== r.root &&
                xt(t, { x: -r.scroll.offset.x, y: -r.scroll.offset.y }),
            o && ((e.x *= o.x.scale), (e.y *= o.y.scale), Qi(t, o)),
            s && ut(r.latestValues) && xt(t, r.latestValues));
    }
    (e.x < ss && e.x > ns && (e.x = 1), e.y < ss && e.y > ns && (e.y = 1));
}
function vt(t, e) {
    ((t.min = t.min + e), (t.max = t.max + e));
}
function is(t, e, n, s, i = 0.5) {
    const r = L(t.min, t.max, i);
    Xe(t, e, n, r, s);
}
function xt(t, e) {
    (is(t.x, e.x, e.scaleX, e.scale, e.originX),
        is(t.y, e.y, e.scaleY, e.scale, e.originY));
}
function to(t, e) {
    return Zi(hl(t.getBoundingClientRect(), e));
}
function dl(t, e, n) {
    const s = to(t, n),
        { scroll: i } = e;
    return (i && (vt(s.x, i.offset.x), vt(s.y, i.offset.y)), s);
}
const ml = {
        x: 'translateX',
        y: 'translateY',
        z: 'translateZ',
        transformPerspective: 'perspective',
    },
    pl = St.length;
function gl(t, e, n) {
    let s = '',
        i = !0;
    for (let r = 0; r < pl; r++) {
        const o = St[r],
            a = t[o];
        if (a === void 0) continue;
        let l = !0;
        if (typeof a == 'number') l = a === (o.startsWith('scale') ? 1 : 0);
        else {
            const c = parseFloat(a);
            l = o.startsWith('scale') ? c === 1 : c === 0;
        }
        if (!l || n) {
            const c = Ki(a, xn[o]);
            if (!l) {
                i = !1;
                const u = ml[o] || o;
                s += `${u}(${c}) `;
            }
            n && (e[o] = c);
        }
    }
    return ((s = s.trim()), n ? (s = n(e, i ? '' : s)) : i && (s = 'none'), s);
}
function An(t, e, n) {
    const { style: s, vars: i, transformOrigin: r } = t;
    let o = !1,
        a = !1;
    for (const l in e) {
        const c = e[l];
        if (bt.has(l)) {
            o = !0;
            continue;
        } else if (hi(l)) {
            i[l] = c;
            continue;
        } else {
            const u = Ki(c, xn[l]);
            l.startsWith('origin') ? ((a = !0), (r[l] = u)) : (s[l] = u);
        }
    }
    if (
        (e.transform ||
            (o || n
                ? (s.transform = gl(e, t.transform, n))
                : s.transform && (s.transform = 'none')),
        a)
    ) {
        const { originX: l = '50%', originY: c = '50%', originZ: u = 0 } = r;
        s.transformOrigin = `${l} ${c} ${u}`;
    }
}
function eo(t, { style: e, vars: n }, s, i) {
    const r = t.style;
    let o;
    for (o in e) r[o] = e[o];
    i?.applyProjectionStyles(r, s);
    for (o in n) r.setProperty(o, n[o]);
}
function os(t, e) {
    return e.max === e.min ? 0 : (t / (e.max - e.min)) * 100;
}
const At = {
        correct: (t, e) => {
            if (!e.target) return t;
            if (typeof t == 'string')
                if (P.test(t)) t = parseFloat(t);
                else return t;
            const n = os(t, e.target.x),
                s = os(t, e.target.y);
            return `${n}% ${s}%`;
        },
    },
    yl = {
        correct: (t, { treeScale: e, projectionDelta: n }) => {
            const s = t,
                i = X.parse(t);
            if (i.length > 5) return s;
            const r = X.createTransformer(t),
                o = typeof i[0] != 'number' ? 1 : 0,
                a = n.x.scale * e.x,
                l = n.y.scale * e.y;
            ((i[0 + o] /= a), (i[1 + o] /= l));
            const c = L(a, l, 0.5);
            return (
                typeof i[2 + o] == 'number' && (i[2 + o] /= c),
                typeof i[3 + o] == 'number' && (i[3 + o] /= c),
                r(i)
            );
        },
    },
    Ye = {
        borderRadius: {
            ...At,
            applyTo: [
                'borderTopLeftRadius',
                'borderTopRightRadius',
                'borderBottomLeftRadius',
                'borderBottomRightRadius',
            ],
        },
        borderTopLeftRadius: At,
        borderTopRightRadius: At,
        borderBottomLeftRadius: At,
        borderBottomRightRadius: At,
        boxShadow: yl,
    };
function no(t, { layout: e, layoutId: n }) {
    return (
        bt.has(t) ||
        t.startsWith('origin') ||
        ((e || n !== void 0) && (!!Ye[t] || t === 'opacity'))
    );
}
function Vn(t, e, n) {
    const s = t.style,
        i = e?.style,
        r = {};
    if (!s) return r;
    for (const o in s)
        (O(s[o]) ||
            (i && O(i[o])) ||
            no(o, t) ||
            n?.getValue(o)?.liveStyle !== void 0) &&
            (r[o] = s[o]);
    return r;
}
function vl(t) {
    return window.getComputedStyle(t);
}
class xl extends qi {
    constructor() {
        (super(...arguments), (this.type = 'html'), (this.renderInstance = eo));
    }
    readValueFromInstance(e, n) {
        if (bt.has(n)) return this.projection?.isProjecting ? Ie(n) : Or(e, n);
        {
            const s = vl(e),
                i = (hi(n) ? s.getPropertyValue(n) : s[n]) || 0;
            return typeof i == 'string' ? i.trim() : i;
        }
    }
    measureInstanceViewportBox(e, { transformPagePoint: n }) {
        return to(e, n);
    }
    build(e, n, s) {
        An(e, n, s.transformTemplate);
    }
    scrapeMotionValuesFromProps(e, n, s) {
        return Vn(e, n, s);
    }
}
const Tl = { offset: 'stroke-dashoffset', array: 'stroke-dasharray' },
    wl = { offset: 'strokeDashoffset', array: 'strokeDasharray' };
function Pl(t, e, n = 1, s = 0, i = !0) {
    t.pathLength = 1;
    const r = i ? Tl : wl;
    ((t[r.offset] = `${-s}`), (t[r.array] = `${e} ${n}`));
}
const Sl = ['offsetDistance', 'offsetPath', 'offsetRotate', 'offsetAnchor'];
function so(
    t,
    {
        attrX: e,
        attrY: n,
        attrScale: s,
        pathLength: i,
        pathSpacing: r = 1,
        pathOffset: o = 0,
        ...a
    },
    l,
    c,
    u,
) {
    if ((An(t, a, c), l)) {
        t.style.viewBox && (t.attrs.viewBox = t.style.viewBox);
        return;
    }
    ((t.attrs = t.style), (t.style = {}));
    const { attrs: h, style: f } = t;
    (h.transform && ((f.transform = h.transform), delete h.transform),
        (f.transform || h.transformOrigin) &&
            ((f.transformOrigin = h.transformOrigin ?? '50% 50%'),
            delete h.transformOrigin),
        f.transform &&
            ((f.transformBox = u?.transformBox ?? 'fill-box'),
            delete h.transformBox));
    for (const d of Sl) h[d] !== void 0 && ((f[d] = h[d]), delete h[d]);
    (e !== void 0 && (h.x = e),
        n !== void 0 && (h.y = n),
        s !== void 0 && (h.scale = s),
        i !== void 0 && Pl(h, i, r, o, !1));
}
const io = new Set([
        'baseFrequency',
        'diffuseConstant',
        'kernelMatrix',
        'kernelUnitLength',
        'keySplines',
        'keyTimes',
        'limitingConeAngle',
        'markerHeight',
        'markerWidth',
        'numOctaves',
        'targetX',
        'targetY',
        'surfaceScale',
        'specularConstant',
        'specularExponent',
        'stdDeviation',
        'tableValues',
        'viewBox',
        'gradientTransform',
        'pathLength',
        'startOffset',
        'textLength',
        'lengthAdjust',
    ]),
    oo = (t) => typeof t == 'string' && t.toLowerCase() === 'svg';
function bl(t, e, n, s) {
    eo(t, e, void 0, s);
    for (const i in e.attrs) t.setAttribute(io.has(i) ? i : vn(i), e.attrs[i]);
}
function ro(t, e, n) {
    const s = Vn(t, e, n);
    for (const i in t)
        if (O(t[i]) || O(e[i])) {
            const r =
                St.indexOf(i) !== -1
                    ? 'attr' + i.charAt(0).toUpperCase() + i.substring(1)
                    : i;
            s[r] = t[i];
        }
    return s;
}
class Al extends qi {
    constructor() {
        (super(...arguments),
            (this.type = 'svg'),
            (this.isSVGTag = !1),
            (this.measureInstanceViewportBox = F));
    }
    getBaseTargetFromProps(e, n) {
        return e[n];
    }
    readValueFromInstance(e, n) {
        if (bt.has(n)) {
            const s = Ni(n);
            return (s && s.default) || 0;
        }
        return ((n = io.has(n) ? n : vn(n)), e.getAttribute(n));
    }
    scrapeMotionValuesFromProps(e, n, s) {
        return ro(e, n, s);
    }
    build(e, n, s) {
        so(e, n, this.isSVGTag, s.transformTemplate, s.style);
    }
    renderInstance(e, n, s, i) {
        bl(e, n, s, i);
    }
    mount(e) {
        ((this.isSVGTag = oo(e.tagName)), super.mount(e));
    }
}
const Vl = bn.length;
function ao(t) {
    if (!t) return;
    if (!t.isControllingVariants) {
        const n = t.parent ? ao(t.parent) || {} : {};
        return (t.props.initial !== void 0 && (n.initial = t.props.initial), n);
    }
    const e = {};
    for (let n = 0; n < Vl; n++) {
        const s = bn[n],
            i = t.props[s];
        (It(i) || i === !1) && (e[s] = i);
    }
    return e;
}
function lo(t, e) {
    if (!Array.isArray(e)) return !1;
    const n = e.length;
    if (n !== t.length) return !1;
    for (let s = 0; s < n; s++) if (e[s] !== t[s]) return !1;
    return !0;
}
const Cl = [...Sn].reverse(),
    Ml = Sn.length;
function Dl(t) {
    return (e) =>
        Promise.all(e.map(({ animation: n, options: s }) => Aa(t, n, s)));
}
function El(t) {
    let e = Dl(t),
        n = rs(),
        s = !0;
    const i = (l) => (c, u) => {
        const h = Tt(t, u, l === 'exit' ? t.presenceContext?.custom : void 0);
        if (h) {
            const { transition: f, transitionEnd: d, ...m } = h;
            c = { ...c, ...m, ...d };
        }
        return c;
    };
    function r(l) {
        e = l(t);
    }
    function o(l) {
        const { props: c } = t,
            u = ao(t.parent) || {},
            h = [],
            f = new Set();
        let d = {},
            m = 1 / 0;
        for (let p = 0; p < Ml; p++) {
            const g = Cl[p],
                x = n[g],
                v = c[g] !== void 0 ? c[g] : u[g],
                S = It(v),
                w = g === l ? x.isActive : null;
            w === !1 && (m = p);
            let A = v === u[g] && v !== c[g] && S;
            if (
                (A && s && t.manuallyAnimateOnMount && (A = !1),
                (x.protectedKeys = { ...d }),
                (!x.isActive && w === null) ||
                    (!v && !x.prevProp) ||
                    le(v) ||
                    typeof v == 'boolean')
            )
                continue;
            if (g === 'exit' && x.isActive && w !== !0) {
                x.prevResolvedValues && (d = { ...d, ...x.prevResolvedValues });
                continue;
            }
            const M = Rl(x.prevProp, v);
            let b = M || (g === l && x.isActive && !A && S) || (p > m && S),
                V = !1;
            const E = Array.isArray(v) ? v : [v];
            let N = E.reduce(i(g), {});
            w === !1 && (N = {});
            const { prevResolvedValues: Y = {} } = x,
                st = { ...Y, ...N },
                tt = (R) => {
                    ((b = !0),
                        f.has(R) && ((V = !0), f.delete(R)),
                        (x.needsAnimating[R] = !0));
                    const j = t.getValue(R);
                    j && (j.liveStyle = !1);
                };
            for (const R in st) {
                const j = N[R],
                    q = Y[R];
                if (d.hasOwnProperty(R)) continue;
                let H = !1;
                (Ue(j) && Ue(q) ? (H = !lo(j, q)) : (H = j !== q),
                    H
                        ? j != null
                            ? tt(R)
                            : f.add(R)
                        : j !== void 0 && f.has(R)
                          ? tt(R)
                          : (x.protectedKeys[R] = !0));
            }
            ((x.prevProp = v),
                (x.prevResolvedValues = N),
                x.isActive && (d = { ...d, ...N }),
                s && t.blockInitialAnimation && (b = !1));
            const et = A && M;
            b &&
                (!et || V) &&
                h.push(
                    ...E.map((R) => {
                        const j = { type: g };
                        if (
                            typeof R == 'string' &&
                            s &&
                            !et &&
                            t.manuallyAnimateOnMount &&
                            t.parent
                        ) {
                            const { parent: q } = t,
                                H = Tt(q, R);
                            if (q.enteringChildren && H) {
                                const { delayChildren: ko } =
                                    H.transition || {};
                                j.delay = Ei(q.enteringChildren, t, ko);
                            }
                        }
                        return { animation: R, options: j };
                    }),
                );
        }
        if (f.size) {
            const p = {};
            if (typeof c.initial != 'boolean') {
                const g = Tt(
                    t,
                    Array.isArray(c.initial) ? c.initial[0] : c.initial,
                );
                g && g.transition && (p.transition = g.transition);
            }
            (f.forEach((g) => {
                const x = t.getBaseTarget(g),
                    v = t.getValue(g);
                (v && (v.liveStyle = !0), (p[g] = x ?? null));
            }),
                h.push({ animation: p }));
        }
        let y = !!h.length;
        return (
            s &&
                (c.initial === !1 || c.initial === c.animate) &&
                !t.manuallyAnimateOnMount &&
                (y = !1),
            (s = !1),
            y ? e(h) : Promise.resolve()
        );
    }
    function a(l, c) {
        if (n[l].isActive === c) return Promise.resolve();
        (t.variantChildren?.forEach((h) => h.animationState?.setActive(l, c)),
            (n[l].isActive = c));
        const u = o(l);
        for (const h in n) n[h].protectedKeys = {};
        return u;
    }
    return {
        animateChanges: o,
        setActive: a,
        setAnimateFunction: r,
        getState: () => n,
        reset: () => {
            n = rs();
        },
    };
}
function Rl(t, e) {
    return typeof e == 'string' ? e !== t : Array.isArray(e) ? !lo(e, t) : !1;
}
function ct(t = !1) {
    return {
        isActive: t,
        protectedKeys: {},
        needsAnimating: {},
        prevResolvedValues: {},
    };
}
function rs() {
    return {
        animate: ct(!0),
        whileInView: ct(),
        whileHover: ct(),
        whileTap: ct(),
        whileDrag: ct(),
        whileFocus: ct(),
        exit: ct(),
    };
}
function as(t, e) {
    ((t.min = e.min), (t.max = e.max));
}
function z(t, e) {
    (as(t.x, e.x), as(t.y, e.y));
}
function ls(t, e) {
    ((t.translate = e.translate),
        (t.scale = e.scale),
        (t.originPoint = e.originPoint),
        (t.origin = e.origin));
}
const co = 1e-4,
    Ll = 1 - co,
    kl = 1 + co,
    uo = 0.01,
    Il = 0 - uo,
    Fl = 0 + uo;
function W(t) {
    return t.max - t.min;
}
function jl(t, e, n) {
    return Math.abs(t - e) <= n;
}
function cs(t, e, n, s = 0.5) {
    ((t.origin = s),
        (t.originPoint = L(e.min, e.max, t.origin)),
        (t.scale = W(n) / W(e)),
        (t.translate = L(n.min, n.max, t.origin) - t.originPoint),
        ((t.scale >= Ll && t.scale <= kl) || isNaN(t.scale)) && (t.scale = 1),
        ((t.translate >= Il && t.translate <= Fl) || isNaN(t.translate)) &&
            (t.translate = 0));
}
function Dt(t, e, n, s) {
    (cs(t.x, e.x, n.x, s ? s.originX : void 0),
        cs(t.y, e.y, n.y, s ? s.originY : void 0));
}
function us(t, e, n) {
    ((t.min = n.min + e.min), (t.max = t.min + W(e)));
}
function Bl(t, e, n) {
    (us(t.x, e.x, n.x), us(t.y, e.y, n.y));
}
function hs(t, e, n) {
    ((t.min = e.min - n.min), (t.max = t.min + W(e)));
}
function oe(t, e, n) {
    (hs(t.x, e.x, n.x), hs(t.y, e.y, n.y));
}
function fs(t, e, n, s, i) {
    return (
        (t -= e),
        (t = ie(t, 1 / n, s)),
        i !== void 0 && (t = ie(t, 1 / i, s)),
        t
    );
}
function Ol(t, e = 0, n = 1, s = 0.5, i, r = t, o = t) {
    if (
        (J.test(e) &&
            ((e = parseFloat(e)), (e = L(o.min, o.max, e / 100) - o.min)),
        typeof e != 'number')
    )
        return;
    let a = L(r.min, r.max, s);
    (t === r && (a -= e),
        (t.min = fs(t.min, e, n, a, i)),
        (t.max = fs(t.max, e, n, a, i)));
}
function ds(t, e, [n, s, i], r, o) {
    Ol(t, e[n], e[s], e[i], e.scale, r, o);
}
const Nl = ['x', 'scaleX', 'originX'],
    Ul = ['y', 'scaleY', 'originY'];
function ms(t, e, n, s) {
    (ds(t.x, e, Nl, n ? n.x : void 0, s ? s.x : void 0),
        ds(t.y, e, Ul, n ? n.y : void 0, s ? s.y : void 0));
}
function ps(t) {
    return t.translate === 0 && t.scale === 1;
}
function ho(t) {
    return ps(t.x) && ps(t.y);
}
function gs(t, e) {
    return t.min === e.min && t.max === e.max;
}
function Wl(t, e) {
    return gs(t.x, e.x) && gs(t.y, e.y);
}
function ys(t, e) {
    return (
        Math.round(t.min) === Math.round(e.min) &&
        Math.round(t.max) === Math.round(e.max)
    );
}
function fo(t, e) {
    return ys(t.x, e.x) && ys(t.y, e.y);
}
function vs(t) {
    return W(t.x) / W(t.y);
}
function xs(t, e) {
    return (
        t.translate === e.translate &&
        t.scale === e.scale &&
        t.originPoint === e.originPoint
    );
}
function Z(t) {
    return [t('x'), t('y')];
}
function Kl(t, e, n) {
    let s = '';
    const i = t.x.translate / e.x,
        r = t.y.translate / e.y,
        o = n?.z || 0;
    if (
        ((i || r || o) && (s = `translate3d(${i}px, ${r}px, ${o}px) `),
        (e.x !== 1 || e.y !== 1) && (s += `scale(${1 / e.x}, ${1 / e.y}) `),
        n)
    ) {
        const {
            transformPerspective: c,
            rotate: u,
            rotateX: h,
            rotateY: f,
            skewX: d,
            skewY: m,
        } = n;
        (c && (s = `perspective(${c}px) ${s}`),
            u && (s += `rotate(${u}deg) `),
            h && (s += `rotateX(${h}deg) `),
            f && (s += `rotateY(${f}deg) `),
            d && (s += `skewX(${d}deg) `),
            m && (s += `skewY(${m}deg) `));
    }
    const a = t.x.scale * e.x,
        l = t.y.scale * e.y;
    return ((a !== 1 || l !== 1) && (s += `scale(${a}, ${l})`), s || 'none');
}
const mo = ['TopLeft', 'TopRight', 'BottomLeft', 'BottomRight'],
    $l = mo.length,
    Ts = (t) => (typeof t == 'string' ? parseFloat(t) : t),
    ws = (t) => typeof t == 'number' || P.test(t);
function Hl(t, e, n, s, i, r) {
    i
        ? ((t.opacity = L(0, n.opacity ?? 1, zl(s))),
          (t.opacityExit = L(e.opacity ?? 1, 0, Gl(s))))
        : r && (t.opacity = L(e.opacity ?? 1, n.opacity ?? 1, s));
    for (let o = 0; o < $l; o++) {
        const a = `border${mo[o]}Radius`;
        let l = Ps(e, a),
            c = Ps(n, a);
        if (l === void 0 && c === void 0) continue;
        (l || (l = 0),
            c || (c = 0),
            l === 0 || c === 0 || ws(l) === ws(c)
                ? ((t[a] = Math.max(L(Ts(l), Ts(c), s), 0)),
                  (J.test(c) || J.test(l)) && (t[a] += '%'))
                : (t[a] = c));
    }
    (e.rotate || n.rotate) && (t.rotate = L(e.rotate || 0, n.rotate || 0, s));
}
function Ps(t, e) {
    return t[e] !== void 0 ? t[e] : t.borderRadius;
}
const zl = po(0, 0.5, oi),
    Gl = po(0.5, 0.95, $);
function po(t, e, n) {
    return (s) => (s < t ? 0 : s > e ? 1 : n(Rt(t, e, s)));
}
function _l(t, e, n) {
    const s = O(t) ? t : wt(t);
    return (s.start(gn('', s, e, n)), s.animation);
}
function Ft(t, e, n, s = { passive: !0 }) {
    return (t.addEventListener(e, n, s), () => t.removeEventListener(e, n));
}
const Xl = (t, e) => t.depth - e.depth;
class Yl {
    constructor() {
        ((this.children = []), (this.isDirty = !1));
    }
    add(e) {
        (tn(this.children, e), (this.isDirty = !0));
    }
    remove(e) {
        (Qt(this.children, e), (this.isDirty = !0));
    }
    forEach(e) {
        (this.isDirty && this.children.sort(Xl),
            (this.isDirty = !1),
            this.children.forEach(e));
    }
}
function ql(t, e) {
    const n = U.now(),
        s = ({ timestamp: i }) => {
            const r = i - n;
            r >= e && (rt(s), t(r - e));
        };
    return (D.setup(s, !0), () => rt(s));
}
function qt(t) {
    return O(t) ? t.get() : t;
}
class Zl {
    constructor() {
        this.members = [];
    }
    add(e) {
        tn(this.members, e);
        for (let n = this.members.length - 1; n >= 0; n--) {
            const s = this.members[n];
            if (s === e || s === this.lead || s === this.prevLead) continue;
            const i = s.instance;
            i &&
                i.isConnected === !1 &&
                s.isPresent !== !1 &&
                !s.snapshot &&
                Qt(this.members, s);
        }
        e.scheduleRender();
    }
    remove(e) {
        if (
            (Qt(this.members, e),
            e === this.prevLead && (this.prevLead = void 0),
            e === this.lead)
        ) {
            const n = this.members[this.members.length - 1];
            n && this.promote(n);
        }
    }
    relegate(e) {
        const n = this.members.findIndex((i) => e === i);
        if (n === 0) return !1;
        let s;
        for (let i = n; i >= 0; i--) {
            const r = this.members[i],
                o = r.instance;
            if (r.isPresent !== !1 && (!o || o.isConnected !== !1)) {
                s = r;
                break;
            }
        }
        return s ? (this.promote(s), !0) : !1;
    }
    promote(e, n) {
        const s = this.lead;
        if (e !== s && ((this.prevLead = s), (this.lead = e), e.show(), s)) {
            (s.instance && s.scheduleRender(), e.scheduleRender());
            const i = s.options.layoutDependency,
                r = e.options.layoutDependency;
            if (!(i !== void 0 && r !== void 0 && i === r)) {
                const l = s.instance;
                (l && l.isConnected === !1 && !s.snapshot) ||
                    ((e.resumeFrom = s),
                    n && (e.resumeFrom.preserveOpacity = !0),
                    s.snapshot &&
                        ((e.snapshot = s.snapshot),
                        (e.snapshot.latestValues =
                            s.animationValues || s.latestValues)),
                    e.root && e.root.isUpdating && (e.isLayoutDirty = !0));
            }
            const { crossfade: a } = e.options;
            a === !1 && s.hide();
        }
    }
    exitAnimationComplete() {
        this.members.forEach((e) => {
            const { options: n, resumingFrom: s } = e;
            (n.onExitComplete && n.onExitComplete(),
                s && s.options.onExitComplete && s.options.onExitComplete());
        });
    }
    scheduleRender() {
        this.members.forEach((e) => {
            e.instance && e.scheduleRender(!1);
        });
    }
    removeLeadSnapshot() {
        this.lead && this.lead.snapshot && (this.lead.snapshot = void 0);
    }
}
const Zt = { hasAnimatedSinceResize: !0, hasEverUpdated: !1 },
    xe = ['', 'X', 'Y', 'Z'],
    Jl = 1e3;
let Ql = 0;
function Te(t, e, n, s) {
    const { latestValues: i } = e;
    i[t] && ((n[t] = i[t]), e.setStaticValue(t, 0), s && (s[t] = 0));
}
function go(t) {
    if (((t.hasCheckedOptimisedAppear = !0), t.root === t)) return;
    const { visualElement: e } = t.options;
    if (!e) return;
    const n = Fi(e);
    if (window.MotionHasOptimisedAnimation(n, 'transform')) {
        const { layout: i, layoutId: r } = t.options;
        window.MotionCancelOptimisedAnimation(n, 'transform', D, !(i || r));
    }
    const { parent: s } = t;
    s && !s.hasCheckedOptimisedAppear && go(s);
}
function yo({
    attachResizeListener: t,
    defaultParent: e,
    measureScroll: n,
    checkIsScrollRoot: s,
    resetTransform: i,
}) {
    return class {
        constructor(o = {}, a = e?.()) {
            ((this.id = Ql++),
                (this.animationId = 0),
                (this.animationCommitId = 0),
                (this.children = new Set()),
                (this.options = {}),
                (this.isTreeAnimating = !1),
                (this.isAnimationBlocked = !1),
                (this.isLayoutDirty = !1),
                (this.isProjectionDirty = !1),
                (this.isSharedProjectionDirty = !1),
                (this.isTransformDirty = !1),
                (this.updateManuallyBlocked = !1),
                (this.updateBlockedByResize = !1),
                (this.isUpdating = !1),
                (this.isSVG = !1),
                (this.needsReset = !1),
                (this.shouldResetTransform = !1),
                (this.hasCheckedOptimisedAppear = !1),
                (this.treeScale = { x: 1, y: 1 }),
                (this.eventHandlers = new Map()),
                (this.hasTreeAnimated = !1),
                (this.layoutVersion = 0),
                (this.updateScheduled = !1),
                (this.scheduleUpdate = () => this.update()),
                (this.projectionUpdateScheduled = !1),
                (this.checkUpdateFailed = () => {
                    this.isUpdating &&
                        ((this.isUpdating = !1), this.clearAllSnapshots());
                }),
                (this.updateProjection = () => {
                    ((this.projectionUpdateScheduled = !1),
                        this.nodes.forEach(nc),
                        this.nodes.forEach(rc),
                        this.nodes.forEach(ac),
                        this.nodes.forEach(sc));
                }),
                (this.resolvedRelativeTargetAt = 0),
                (this.linkedParentVersion = 0),
                (this.hasProjected = !1),
                (this.isVisible = !0),
                (this.animationProgress = 0),
                (this.sharedNodes = new Map()),
                (this.latestValues = o),
                (this.root = a ? a.root || a : this),
                (this.path = a ? [...a.path, a] : []),
                (this.parent = a),
                (this.depth = a ? a.depth + 1 : 0));
            for (let l = 0; l < this.path.length; l++)
                this.path[l].shouldResetTransform = !0;
            this.root === this && (this.nodes = new Yl());
        }
        addEventListener(o, a) {
            return (
                this.eventHandlers.has(o) ||
                    this.eventHandlers.set(o, new nn()),
                this.eventHandlers.get(o).add(a)
            );
        }
        notifyListeners(o, ...a) {
            const l = this.eventHandlers.get(o);
            l && l.notify(...a);
        }
        hasListeners(o) {
            return this.eventHandlers.has(o);
        }
        mount(o) {
            if (this.instance) return;
            ((this.isSVG = Pn(o) && !el(o)), (this.instance = o));
            const { layoutId: a, layout: l, visualElement: c } = this.options;
            if (
                (c && !c.current && c.mount(o),
                this.root.nodes.add(this),
                this.parent && this.parent.children.add(this),
                this.root.hasTreeAnimated &&
                    (l || a) &&
                    (this.isLayoutDirty = !0),
                t)
            ) {
                let u,
                    h = 0;
                const f = () => (this.root.updateBlockedByResize = !1);
                (D.read(() => {
                    h = window.innerWidth;
                }),
                    t(o, () => {
                        const d = window.innerWidth;
                        d !== h &&
                            ((h = d),
                            (this.root.updateBlockedByResize = !0),
                            u && u(),
                            (u = ql(f, 250)),
                            Zt.hasAnimatedSinceResize &&
                                ((Zt.hasAnimatedSinceResize = !1),
                                this.nodes.forEach(As)));
                    }));
            }
            (a && this.root.registerSharedNode(a, this),
                this.options.animate !== !1 &&
                    c &&
                    (a || l) &&
                    this.addEventListener(
                        'didUpdate',
                        ({
                            delta: u,
                            hasLayoutChanged: h,
                            hasRelativeLayoutChanged: f,
                            layout: d,
                        }) => {
                            if (this.isTreeAnimationBlocked()) {
                                ((this.target = void 0),
                                    (this.relativeTarget = void 0));
                                return;
                            }
                            const m =
                                    this.options.transition ||
                                    c.getDefaultTransition() ||
                                    fc,
                                {
                                    onLayoutAnimationStart: y,
                                    onLayoutAnimationComplete: p,
                                } = c.getProps(),
                                g =
                                    !this.targetLayout ||
                                    !fo(this.targetLayout, d),
                                x = !h && f;
                            if (
                                this.options.layoutRoot ||
                                this.resumeFrom ||
                                x ||
                                (h && (g || !this.currentAnimation))
                            ) {
                                this.resumeFrom &&
                                    ((this.resumingFrom = this.resumeFrom),
                                    (this.resumingFrom.resumingFrom = void 0));
                                const v = {
                                    ...pn(m, 'layout'),
                                    onPlay: y,
                                    onComplete: p,
                                };
                                ((c.shouldReduceMotion ||
                                    this.options.layoutRoot) &&
                                    ((v.delay = 0), (v.type = !1)),
                                    this.startAnimation(v),
                                    this.setAnimationOrigin(u, x));
                            } else
                                (h || As(this),
                                    this.isLead() &&
                                        this.options.onExitComplete &&
                                        this.options.onExitComplete());
                            this.targetLayout = d;
                        },
                    ));
        }
        unmount() {
            (this.options.layoutId && this.willUpdate(),
                this.root.nodes.remove(this));
            const o = this.getStack();
            (o && o.remove(this),
                this.parent && this.parent.children.delete(this),
                (this.instance = void 0),
                this.eventHandlers.clear(),
                rt(this.updateProjection));
        }
        blockUpdate() {
            this.updateManuallyBlocked = !0;
        }
        unblockUpdate() {
            this.updateManuallyBlocked = !1;
        }
        isUpdateBlocked() {
            return this.updateManuallyBlocked || this.updateBlockedByResize;
        }
        isTreeAnimationBlocked() {
            return (
                this.isAnimationBlocked ||
                (this.parent && this.parent.isTreeAnimationBlocked()) ||
                !1
            );
        }
        startUpdate() {
            this.isUpdateBlocked() ||
                ((this.isUpdating = !0),
                this.nodes && this.nodes.forEach(lc),
                this.animationId++);
        }
        getTransformTemplate() {
            const { visualElement: o } = this.options;
            return o && o.getProps().transformTemplate;
        }
        willUpdate(o = !0) {
            if (
                ((this.root.hasTreeAnimated = !0), this.root.isUpdateBlocked())
            ) {
                this.options.onExitComplete && this.options.onExitComplete();
                return;
            }
            if (
                (window.MotionCancelOptimisedAnimation &&
                    !this.hasCheckedOptimisedAppear &&
                    go(this),
                !this.root.isUpdating && this.root.startUpdate(),
                this.isLayoutDirty)
            )
                return;
            this.isLayoutDirty = !0;
            for (let u = 0; u < this.path.length; u++) {
                const h = this.path[u];
                ((h.shouldResetTransform = !0),
                    h.updateScroll('snapshot'),
                    h.options.layoutRoot && h.willUpdate(!1));
            }
            const { layoutId: a, layout: l } = this.options;
            if (a === void 0 && !l) return;
            const c = this.getTransformTemplate();
            ((this.prevTransformTemplateValue = c
                ? c(this.latestValues, '')
                : void 0),
                this.updateSnapshot(),
                o && this.notifyListeners('willUpdate'));
        }
        update() {
            if (((this.updateScheduled = !1), this.isUpdateBlocked())) {
                (this.unblockUpdate(),
                    this.clearAllSnapshots(),
                    this.nodes.forEach(Ss));
                return;
            }
            if (this.animationId <= this.animationCommitId) {
                this.nodes.forEach(bs);
                return;
            }
            ((this.animationCommitId = this.animationId),
                this.isUpdating
                    ? ((this.isUpdating = !1),
                      this.nodes.forEach(oc),
                      this.nodes.forEach(tc),
                      this.nodes.forEach(ec))
                    : this.nodes.forEach(bs),
                this.clearAllSnapshots());
            const a = U.now();
            ((B.delta = Q(0, 1e3 / 60, a - B.timestamp)),
                (B.timestamp = a),
                (B.isProcessing = !0),
                he.update.process(B),
                he.preRender.process(B),
                he.render.process(B),
                (B.isProcessing = !1));
        }
        didUpdate() {
            this.updateScheduled ||
                ((this.updateScheduled = !0), Tn.read(this.scheduleUpdate));
        }
        clearAllSnapshots() {
            (this.nodes.forEach(ic), this.sharedNodes.forEach(cc));
        }
        scheduleUpdateProjection() {
            this.projectionUpdateScheduled ||
                ((this.projectionUpdateScheduled = !0),
                D.preRender(this.updateProjection, !1, !0));
        }
        scheduleCheckAfterUnmount() {
            D.postRender(() => {
                this.isLayoutDirty
                    ? this.root.didUpdate()
                    : this.root.checkUpdateFailed();
            });
        }
        updateSnapshot() {
            this.snapshot ||
                !this.instance ||
                ((this.snapshot = this.measure()),
                this.snapshot &&
                    !W(this.snapshot.measuredBox.x) &&
                    !W(this.snapshot.measuredBox.y) &&
                    (this.snapshot = void 0));
        }
        updateLayout() {
            if (
                !this.instance ||
                (this.updateScroll(),
                !(this.options.alwaysMeasureLayout && this.isLead()) &&
                    !this.isLayoutDirty)
            )
                return;
            if (this.resumeFrom && !this.resumeFrom.instance)
                for (let l = 0; l < this.path.length; l++)
                    this.path[l].updateScroll();
            const o = this.layout;
            ((this.layout = this.measure(!1)),
                this.layoutVersion++,
                (this.layoutCorrected = F()),
                (this.isLayoutDirty = !1),
                (this.projectionDelta = void 0),
                this.notifyListeners('measure', this.layout.layoutBox));
            const { visualElement: a } = this.options;
            a &&
                a.notify(
                    'LayoutMeasure',
                    this.layout.layoutBox,
                    o ? o.layoutBox : void 0,
                );
        }
        updateScroll(o = 'measure') {
            let a = !!(this.options.layoutScroll && this.instance);
            if (
                (this.scroll &&
                    this.scroll.animationId === this.root.animationId &&
                    this.scroll.phase === o &&
                    (a = !1),
                a && this.instance)
            ) {
                const l = s(this.instance);
                this.scroll = {
                    animationId: this.root.animationId,
                    phase: o,
                    isRoot: l,
                    offset: n(this.instance),
                    wasRoot: this.scroll ? this.scroll.isRoot : l,
                };
            }
        }
        resetTransform() {
            if (!i) return;
            const o =
                    this.isLayoutDirty ||
                    this.shouldResetTransform ||
                    this.options.alwaysMeasureLayout,
                a = this.projectionDelta && !ho(this.projectionDelta),
                l = this.getTransformTemplate(),
                c = l ? l(this.latestValues, '') : void 0,
                u = c !== this.prevTransformTemplateValue;
            o &&
                this.instance &&
                (a || ut(this.latestValues) || u) &&
                (i(this.instance, c),
                (this.shouldResetTransform = !1),
                this.scheduleRender());
        }
        measure(o = !0) {
            const a = this.measurePageBox();
            let l = this.removeElementScroll(a);
            return (
                o && (l = this.removeTransform(l)),
                dc(l),
                {
                    animationId: this.root.animationId,
                    measuredBox: a,
                    layoutBox: l,
                    latestValues: {},
                    source: this.id,
                }
            );
        }
        measurePageBox() {
            const { visualElement: o } = this.options;
            if (!o) return F();
            const a = o.measureViewportBox();
            if (!(this.scroll?.wasRoot || this.path.some(mc))) {
                const { scroll: c } = this.root;
                c && (vt(a.x, c.offset.x), vt(a.y, c.offset.y));
            }
            return a;
        }
        removeElementScroll(o) {
            const a = F();
            if ((z(a, o), this.scroll?.wasRoot)) return a;
            for (let l = 0; l < this.path.length; l++) {
                const c = this.path[l],
                    { scroll: u, options: h } = c;
                c !== this.root &&
                    u &&
                    h.layoutScroll &&
                    (u.wasRoot && z(a, o),
                    vt(a.x, u.offset.x),
                    vt(a.y, u.offset.y));
            }
            return a;
        }
        applyTransform(o, a = !1) {
            const l = F();
            z(l, o);
            for (let c = 0; c < this.path.length; c++) {
                const u = this.path[c];
                (!a &&
                    u.options.layoutScroll &&
                    u.scroll &&
                    u !== u.root &&
                    xt(l, { x: -u.scroll.offset.x, y: -u.scroll.offset.y }),
                    ut(u.latestValues) && xt(l, u.latestValues));
            }
            return (ut(this.latestValues) && xt(l, this.latestValues), l);
        }
        removeTransform(o) {
            const a = F();
            z(a, o);
            for (let l = 0; l < this.path.length; l++) {
                const c = this.path[l];
                if (!c.instance || !ut(c.latestValues)) continue;
                _e(c.latestValues) && c.updateSnapshot();
                const u = F(),
                    h = c.measurePageBox();
                (z(u, h),
                    ms(
                        a,
                        c.latestValues,
                        c.snapshot ? c.snapshot.layoutBox : void 0,
                        u,
                    ));
            }
            return (ut(this.latestValues) && ms(a, this.latestValues), a);
        }
        setTargetDelta(o) {
            ((this.targetDelta = o),
                this.root.scheduleUpdateProjection(),
                (this.isProjectionDirty = !0));
        }
        setOptions(o) {
            this.options = {
                ...this.options,
                ...o,
                crossfade: o.crossfade !== void 0 ? o.crossfade : !0,
            };
        }
        clearMeasurements() {
            ((this.scroll = void 0),
                (this.layout = void 0),
                (this.snapshot = void 0),
                (this.prevTransformTemplateValue = void 0),
                (this.targetDelta = void 0),
                (this.target = void 0),
                (this.isLayoutDirty = !1));
        }
        forceRelativeParentToResolveTarget() {
            this.relativeParent &&
                this.relativeParent.resolvedRelativeTargetAt !== B.timestamp &&
                this.relativeParent.resolveTargetDelta(!0);
        }
        resolveTargetDelta(o = !1) {
            const a = this.getLead();
            (this.isProjectionDirty ||
                (this.isProjectionDirty = a.isProjectionDirty),
                this.isTransformDirty ||
                    (this.isTransformDirty = a.isTransformDirty),
                this.isSharedProjectionDirty ||
                    (this.isSharedProjectionDirty = a.isSharedProjectionDirty));
            const l = !!this.resumingFrom || this !== a;
            if (
                !(
                    o ||
                    (l && this.isSharedProjectionDirty) ||
                    this.isProjectionDirty ||
                    this.parent?.isProjectionDirty ||
                    this.attemptToResolveRelativeTarget ||
                    this.root.updateBlockedByResize
                )
            )
                return;
            const { layout: u, layoutId: h } = this.options;
            if (!this.layout || !(u || h)) return;
            this.resolvedRelativeTargetAt = B.timestamp;
            const f = this.getClosestProjectingParent();
            (f &&
                this.linkedParentVersion !== f.layoutVersion &&
                !f.options.layoutRoot &&
                this.removeRelativeTarget(),
                !this.targetDelta &&
                    !this.relativeTarget &&
                    (f && f.layout
                        ? this.createRelativeTarget(
                              f,
                              this.layout.layoutBox,
                              f.layout.layoutBox,
                          )
                        : this.removeRelativeTarget()),
                !(!this.relativeTarget && !this.targetDelta) &&
                    (this.target ||
                        ((this.target = F()),
                        (this.targetWithTransforms = F())),
                    this.relativeTarget &&
                    this.relativeTargetOrigin &&
                    this.relativeParent &&
                    this.relativeParent.target
                        ? (this.forceRelativeParentToResolveTarget(),
                          Bl(
                              this.target,
                              this.relativeTarget,
                              this.relativeParent.target,
                          ))
                        : this.targetDelta
                          ? (this.resumingFrom
                                ? (this.target = this.applyTransform(
                                      this.layout.layoutBox,
                                  ))
                                : z(this.target, this.layout.layoutBox),
                            Qi(this.target, this.targetDelta))
                          : z(this.target, this.layout.layoutBox),
                    this.attemptToResolveRelativeTarget &&
                        ((this.attemptToResolveRelativeTarget = !1),
                        f &&
                        !!f.resumingFrom == !!this.resumingFrom &&
                        !f.options.layoutScroll &&
                        f.target &&
                        this.animationProgress !== 1
                            ? this.createRelativeTarget(
                                  f,
                                  this.target,
                                  f.target,
                              )
                            : (this.relativeParent = this.relativeTarget =
                                  void 0))));
        }
        getClosestProjectingParent() {
            if (
                !(
                    !this.parent ||
                    _e(this.parent.latestValues) ||
                    Ji(this.parent.latestValues)
                )
            )
                return this.parent.isProjecting()
                    ? this.parent
                    : this.parent.getClosestProjectingParent();
        }
        isProjecting() {
            return !!(
                (this.relativeTarget ||
                    this.targetDelta ||
                    this.options.layoutRoot) &&
                this.layout
            );
        }
        createRelativeTarget(o, a, l) {
            ((this.relativeParent = o),
                (this.linkedParentVersion = o.layoutVersion),
                this.forceRelativeParentToResolveTarget(),
                (this.relativeTarget = F()),
                (this.relativeTargetOrigin = F()),
                oe(this.relativeTargetOrigin, a, l),
                z(this.relativeTarget, this.relativeTargetOrigin));
        }
        removeRelativeTarget() {
            this.relativeParent = this.relativeTarget = void 0;
        }
        calcProjection() {
            const o = this.getLead(),
                a = !!this.resumingFrom || this !== o;
            let l = !0;
            if (
                ((this.isProjectionDirty || this.parent?.isProjectionDirty) &&
                    (l = !1),
                a &&
                    (this.isSharedProjectionDirty || this.isTransformDirty) &&
                    (l = !1),
                this.resolvedRelativeTargetAt === B.timestamp && (l = !1),
                l)
            )
                return;
            const { layout: c, layoutId: u } = this.options;
            if (
                ((this.isTreeAnimating = !!(
                    (this.parent && this.parent.isTreeAnimating) ||
                    this.currentAnimation ||
                    this.pendingAnimation
                )),
                this.isTreeAnimating ||
                    (this.targetDelta = this.relativeTarget = void 0),
                !this.layout || !(c || u))
            )
                return;
            z(this.layoutCorrected, this.layout.layoutBox);
            const h = this.treeScale.x,
                f = this.treeScale.y;
            (fl(this.layoutCorrected, this.treeScale, this.path, a),
                o.layout &&
                    !o.target &&
                    (this.treeScale.x !== 1 || this.treeScale.y !== 1) &&
                    ((o.target = o.layout.layoutBox),
                    (o.targetWithTransforms = F())));
            const { target: d } = o;
            if (!d) {
                this.prevProjectionDelta &&
                    (this.createProjectionDeltas(), this.scheduleRender());
                return;
            }
            (!this.projectionDelta || !this.prevProjectionDelta
                ? this.createProjectionDeltas()
                : (ls(this.prevProjectionDelta.x, this.projectionDelta.x),
                  ls(this.prevProjectionDelta.y, this.projectionDelta.y)),
                Dt(
                    this.projectionDelta,
                    this.layoutCorrected,
                    d,
                    this.latestValues,
                ),
                (this.treeScale.x !== h ||
                    this.treeScale.y !== f ||
                    !xs(this.projectionDelta.x, this.prevProjectionDelta.x) ||
                    !xs(this.projectionDelta.y, this.prevProjectionDelta.y)) &&
                    ((this.hasProjected = !0),
                    this.scheduleRender(),
                    this.notifyListeners('projectionUpdate', d)));
        }
        hide() {
            this.isVisible = !1;
        }
        show() {
            this.isVisible = !0;
        }
        scheduleRender(o = !0) {
            if ((this.options.visualElement?.scheduleRender(), o)) {
                const a = this.getStack();
                a && a.scheduleRender();
            }
            this.resumingFrom &&
                !this.resumingFrom.instance &&
                (this.resumingFrom = void 0);
        }
        createProjectionDeltas() {
            ((this.prevProjectionDelta = yt()),
                (this.projectionDelta = yt()),
                (this.projectionDeltaWithTransform = yt()));
        }
        setAnimationOrigin(o, a = !1) {
            const l = this.snapshot,
                c = l ? l.latestValues : {},
                u = { ...this.latestValues },
                h = yt();
            ((!this.relativeParent ||
                !this.relativeParent.options.layoutRoot) &&
                (this.relativeTarget = this.relativeTargetOrigin = void 0),
                (this.attemptToResolveRelativeTarget = !a));
            const f = F(),
                d = l ? l.source : void 0,
                m = this.layout ? this.layout.source : void 0,
                y = d !== m,
                p = this.getStack(),
                g = !p || p.members.length <= 1,
                x = !!(
                    y &&
                    !g &&
                    this.options.crossfade === !0 &&
                    !this.path.some(hc)
                );
            this.animationProgress = 0;
            let v;
            ((this.mixTargetDelta = (S) => {
                const w = S / 1e3;
                (Vs(h.x, o.x, w),
                    Vs(h.y, o.y, w),
                    this.setTargetDelta(h),
                    this.relativeTarget &&
                        this.relativeTargetOrigin &&
                        this.layout &&
                        this.relativeParent &&
                        this.relativeParent.layout &&
                        (oe(
                            f,
                            this.layout.layoutBox,
                            this.relativeParent.layout.layoutBox,
                        ),
                        uc(
                            this.relativeTarget,
                            this.relativeTargetOrigin,
                            f,
                            w,
                        ),
                        v &&
                            Wl(this.relativeTarget, v) &&
                            (this.isProjectionDirty = !1),
                        v || (v = F()),
                        z(v, this.relativeTarget)),
                    y &&
                        ((this.animationValues = u),
                        Hl(u, c, this.latestValues, w, x, g)),
                    this.root.scheduleUpdateProjection(),
                    this.scheduleRender(),
                    (this.animationProgress = w));
            }),
                this.mixTargetDelta(this.options.layoutRoot ? 1e3 : 0));
        }
        startAnimation(o) {
            (this.notifyListeners('animationStart'),
                this.currentAnimation?.stop(),
                this.resumingFrom?.currentAnimation?.stop(),
                this.pendingAnimation &&
                    (rt(this.pendingAnimation),
                    (this.pendingAnimation = void 0)),
                (this.pendingAnimation = D.update(() => {
                    ((Zt.hasAnimatedSinceResize = !0),
                        this.motionValue || (this.motionValue = wt(0)),
                        this.motionValue.jump(0, !1),
                        (this.currentAnimation = _l(
                            this.motionValue,
                            [0, 1e3],
                            {
                                ...o,
                                velocity: 0,
                                isSync: !0,
                                onUpdate: (a) => {
                                    (this.mixTargetDelta(a),
                                        o.onUpdate && o.onUpdate(a));
                                },
                                onStop: () => {},
                                onComplete: () => {
                                    (o.onComplete && o.onComplete(),
                                        this.completeAnimation());
                                },
                            },
                        )),
                        this.resumingFrom &&
                            (this.resumingFrom.currentAnimation =
                                this.currentAnimation),
                        (this.pendingAnimation = void 0));
                })));
        }
        completeAnimation() {
            this.resumingFrom &&
                ((this.resumingFrom.currentAnimation = void 0),
                (this.resumingFrom.preserveOpacity = void 0));
            const o = this.getStack();
            (o && o.exitAnimationComplete(),
                (this.resumingFrom =
                    this.currentAnimation =
                    this.animationValues =
                        void 0),
                this.notifyListeners('animationComplete'));
        }
        finishAnimation() {
            (this.currentAnimation &&
                (this.mixTargetDelta && this.mixTargetDelta(Jl),
                this.currentAnimation.stop()),
                this.completeAnimation());
        }
        applyTransformsToTarget() {
            const o = this.getLead();
            let {
                targetWithTransforms: a,
                target: l,
                layout: c,
                latestValues: u,
            } = o;
            if (!(!a || !l || !c)) {
                if (
                    this !== o &&
                    this.layout &&
                    c &&
                    vo(
                        this.options.animationType,
                        this.layout.layoutBox,
                        c.layoutBox,
                    )
                ) {
                    l = this.target || F();
                    const h = W(this.layout.layoutBox.x);
                    ((l.x.min = o.target.x.min), (l.x.max = l.x.min + h));
                    const f = W(this.layout.layoutBox.y);
                    ((l.y.min = o.target.y.min), (l.y.max = l.y.min + f));
                }
                (z(a, l),
                    xt(a, u),
                    Dt(
                        this.projectionDeltaWithTransform,
                        this.layoutCorrected,
                        a,
                        u,
                    ));
            }
        }
        registerSharedNode(o, a) {
            (this.sharedNodes.has(o) || this.sharedNodes.set(o, new Zl()),
                this.sharedNodes.get(o).add(a));
            const c = a.options.initialPromotionConfig;
            a.promote({
                transition: c ? c.transition : void 0,
                preserveFollowOpacity:
                    c && c.shouldPreserveFollowOpacity
                        ? c.shouldPreserveFollowOpacity(a)
                        : void 0,
            });
        }
        isLead() {
            const o = this.getStack();
            return o ? o.lead === this : !0;
        }
        getLead() {
            const { layoutId: o } = this.options;
            return o ? this.getStack()?.lead || this : this;
        }
        getPrevLead() {
            const { layoutId: o } = this.options;
            return o ? this.getStack()?.prevLead : void 0;
        }
        getStack() {
            const { layoutId: o } = this.options;
            if (o) return this.root.sharedNodes.get(o);
        }
        promote({
            needsReset: o,
            transition: a,
            preserveFollowOpacity: l,
        } = {}) {
            const c = this.getStack();
            (c && c.promote(this, l),
                o && ((this.projectionDelta = void 0), (this.needsReset = !0)),
                a && this.setOptions({ transition: a }));
        }
        relegate() {
            const o = this.getStack();
            return o ? o.relegate(this) : !1;
        }
        resetSkewAndRotation() {
            const { visualElement: o } = this.options;
            if (!o) return;
            let a = !1;
            const { latestValues: l } = o;
            if (
                ((l.z ||
                    l.rotate ||
                    l.rotateX ||
                    l.rotateY ||
                    l.rotateZ ||
                    l.skewX ||
                    l.skewY) &&
                    (a = !0),
                !a)
            )
                return;
            const c = {};
            l.z && Te('z', o, c, this.animationValues);
            for (let u = 0; u < xe.length; u++)
                (Te(`rotate${xe[u]}`, o, c, this.animationValues),
                    Te(`skew${xe[u]}`, o, c, this.animationValues));
            o.render();
            for (const u in c)
                (o.setStaticValue(u, c[u]),
                    this.animationValues && (this.animationValues[u] = c[u]));
            o.scheduleRender();
        }
        applyProjectionStyles(o, a) {
            if (!this.instance || this.isSVG) return;
            if (!this.isVisible) {
                o.visibility = 'hidden';
                return;
            }
            const l = this.getTransformTemplate();
            if (this.needsReset) {
                ((this.needsReset = !1),
                    (o.visibility = ''),
                    (o.opacity = ''),
                    (o.pointerEvents = qt(a?.pointerEvents) || ''),
                    (o.transform = l ? l(this.latestValues, '') : 'none'));
                return;
            }
            const c = this.getLead();
            if (!this.projectionDelta || !this.layout || !c.target) {
                (this.options.layoutId &&
                    ((o.opacity =
                        this.latestValues.opacity !== void 0
                            ? this.latestValues.opacity
                            : 1),
                    (o.pointerEvents = qt(a?.pointerEvents) || '')),
                    this.hasProjected &&
                        !ut(this.latestValues) &&
                        ((o.transform = l ? l({}, '') : 'none'),
                        (this.hasProjected = !1)));
                return;
            }
            o.visibility = '';
            const u = c.animationValues || c.latestValues;
            this.applyTransformsToTarget();
            let h = Kl(this.projectionDeltaWithTransform, this.treeScale, u);
            (l && (h = l(u, h)), (o.transform = h));
            const { x: f, y: d } = this.projectionDelta;
            ((o.transformOrigin = `${f.origin * 100}% ${d.origin * 100}% 0`),
                c.animationValues
                    ? (o.opacity =
                          c === this
                              ? (u.opacity ?? this.latestValues.opacity ?? 1)
                              : this.preserveOpacity
                                ? this.latestValues.opacity
                                : u.opacityExit)
                    : (o.opacity =
                          c === this
                              ? u.opacity !== void 0
                                  ? u.opacity
                                  : ''
                              : u.opacityExit !== void 0
                                ? u.opacityExit
                                : 0));
            for (const m in Ye) {
                if (u[m] === void 0) continue;
                const { correct: y, applyTo: p, isCSSVariable: g } = Ye[m],
                    x = h === 'none' ? u[m] : y(u[m], c);
                if (p) {
                    const v = p.length;
                    for (let S = 0; S < v; S++) o[p[S]] = x;
                } else
                    g
                        ? (this.options.visualElement.renderState.vars[m] = x)
                        : (o[m] = x);
            }
            this.options.layoutId &&
                (o.pointerEvents =
                    c === this ? qt(a?.pointerEvents) || '' : 'none');
        }
        clearSnapshot() {
            this.resumeFrom = this.snapshot = void 0;
        }
        resetTree() {
            (this.root.nodes.forEach((o) => o.currentAnimation?.stop()),
                this.root.nodes.forEach(Ss),
                this.root.sharedNodes.clear());
        }
    };
}
function tc(t) {
    t.updateLayout();
}
function ec(t) {
    const e = t.resumeFrom?.snapshot || t.snapshot;
    if (t.isLead() && t.layout && e && t.hasListeners('didUpdate')) {
        const { layoutBox: n, measuredBox: s } = t.layout,
            { animationType: i } = t.options,
            r = e.source !== t.layout.source;
        i === 'size'
            ? Z((u) => {
                  const h = r ? e.measuredBox[u] : e.layoutBox[u],
                      f = W(h);
                  ((h.min = n[u].min), (h.max = h.min + f));
              })
            : vo(i, e.layoutBox, n) &&
              Z((u) => {
                  const h = r ? e.measuredBox[u] : e.layoutBox[u],
                      f = W(n[u]);
                  ((h.max = h.min + f),
                      t.relativeTarget &&
                          !t.currentAnimation &&
                          ((t.isProjectionDirty = !0),
                          (t.relativeTarget[u].max =
                              t.relativeTarget[u].min + f)));
              });
        const o = yt();
        Dt(o, n, e.layoutBox);
        const a = yt();
        r
            ? Dt(a, t.applyTransform(s, !0), e.measuredBox)
            : Dt(a, n, e.layoutBox);
        const l = !ho(o);
        let c = !1;
        if (!t.resumeFrom) {
            const u = t.getClosestProjectingParent();
            if (u && !u.resumeFrom) {
                const { snapshot: h, layout: f } = u;
                if (h && f) {
                    const d = F();
                    oe(d, e.layoutBox, h.layoutBox);
                    const m = F();
                    (oe(m, n, f.layoutBox),
                        fo(d, m) || (c = !0),
                        u.options.layoutRoot &&
                            ((t.relativeTarget = m),
                            (t.relativeTargetOrigin = d),
                            (t.relativeParent = u)));
                }
            }
        }
        t.notifyListeners('didUpdate', {
            layout: n,
            snapshot: e,
            delta: a,
            layoutDelta: o,
            hasLayoutChanged: l,
            hasRelativeLayoutChanged: c,
        });
    } else if (t.isLead()) {
        const { onExitComplete: n } = t.options;
        n && n();
    }
    t.options.transition = void 0;
}
function nc(t) {
    t.parent &&
        (t.isProjecting() || (t.isProjectionDirty = t.parent.isProjectionDirty),
        t.isSharedProjectionDirty ||
            (t.isSharedProjectionDirty = !!(
                t.isProjectionDirty ||
                t.parent.isProjectionDirty ||
                t.parent.isSharedProjectionDirty
            )),
        t.isTransformDirty || (t.isTransformDirty = t.parent.isTransformDirty));
}
function sc(t) {
    t.isProjectionDirty = t.isSharedProjectionDirty = t.isTransformDirty = !1;
}
function ic(t) {
    t.clearSnapshot();
}
function Ss(t) {
    t.clearMeasurements();
}
function bs(t) {
    t.isLayoutDirty = !1;
}
function oc(t) {
    const { visualElement: e } = t.options;
    (e && e.getProps().onBeforeLayoutMeasure && e.notify('BeforeLayoutMeasure'),
        t.resetTransform());
}
function As(t) {
    (t.finishAnimation(),
        (t.targetDelta = t.relativeTarget = t.target = void 0),
        (t.isProjectionDirty = !0));
}
function rc(t) {
    t.resolveTargetDelta();
}
function ac(t) {
    t.calcProjection();
}
function lc(t) {
    t.resetSkewAndRotation();
}
function cc(t) {
    t.removeLeadSnapshot();
}
function Vs(t, e, n) {
    ((t.translate = L(e.translate, 0, n)),
        (t.scale = L(e.scale, 1, n)),
        (t.origin = e.origin),
        (t.originPoint = e.originPoint));
}
function Cs(t, e, n, s) {
    ((t.min = L(e.min, n.min, s)), (t.max = L(e.max, n.max, s)));
}
function uc(t, e, n, s) {
    (Cs(t.x, e.x, n.x, s), Cs(t.y, e.y, n.y, s));
}
function hc(t) {
    return t.animationValues && t.animationValues.opacityExit !== void 0;
}
const fc = { duration: 0.45, ease: [0.4, 0, 0.1, 1] },
    Ms = (t) =>
        typeof navigator < 'u' &&
        navigator.userAgent &&
        navigator.userAgent.toLowerCase().includes(t),
    Ds = Ms('applewebkit/') && !Ms('chrome/') ? Math.round : $;
function Es(t) {
    ((t.min = Ds(t.min)), (t.max = Ds(t.max)));
}
function dc(t) {
    (Es(t.x), Es(t.y));
}
function vo(t, e, n) {
    return (
        t === 'position' || (t === 'preserve-aspect' && !jl(vs(e), vs(n), 0.2))
    );
}
function mc(t) {
    return t !== t.root && t.scroll?.wasRoot;
}
const pc = yo({
        attachResizeListener: (t, e) => Ft(t, 'resize', e),
        measureScroll: () => ({
            x:
                document.documentElement.scrollLeft ||
                document.body?.scrollLeft ||
                0,
            y:
                document.documentElement.scrollTop ||
                document.body?.scrollTop ||
                0,
        }),
        checkIsScrollRoot: () => !0,
    }),
    we = { current: void 0 },
    xo = yo({
        measureScroll: (t) => ({ x: t.scrollLeft, y: t.scrollTop }),
        defaultParent: () => {
            if (!we.current) {
                const t = new pc({});
                (t.mount(window),
                    t.setOptions({ layoutScroll: !0 }),
                    (we.current = t));
            }
            return we.current;
        },
        resetTransform: (t, e) => {
            t.style.transform = e !== void 0 ? e : 'none';
        },
        checkIsScrollRoot: (t) =>
            window.getComputedStyle(t).position === 'fixed',
    }),
    Cn = T.createContext({
        transformPagePoint: (t) => t,
        isStatic: !1,
        reducedMotion: 'never',
    });
function Rs(t, e) {
    if (typeof t == 'function') return t(e);
    t != null && (t.current = e);
}
function gc(...t) {
    return (e) => {
        let n = !1;
        const s = t.map((i) => {
            const r = Rs(i, e);
            return (!n && typeof r == 'function' && (n = !0), r);
        });
        if (n)
            return () => {
                for (let i = 0; i < s.length; i++) {
                    const r = s[i];
                    typeof r == 'function' ? r() : Rs(t[i], null);
                }
            };
    };
}
function yc(...t) {
    return T.useCallback(gc(...t), t);
}
class vc extends T.Component {
    getSnapshotBeforeUpdate(e) {
        const n = this.props.childRef.current;
        if (
            n &&
            e.isPresent &&
            !this.props.isPresent &&
            this.props.pop !== !1
        ) {
            const s = n.offsetParent,
                i = (ze(s) && s.offsetWidth) || 0,
                r = (ze(s) && s.offsetHeight) || 0,
                o = this.props.sizeRef.current;
            ((o.height = n.offsetHeight || 0),
                (o.width = n.offsetWidth || 0),
                (o.top = n.offsetTop),
                (o.left = n.offsetLeft),
                (o.right = i - o.width - o.left),
                (o.bottom = r - o.height - o.top));
        }
        return null;
    }
    componentDidUpdate() {}
    render() {
        return this.props.children;
    }
}
function xc({
    children: t,
    isPresent: e,
    anchorX: n,
    anchorY: s,
    root: i,
    pop: r,
}) {
    const o = T.useId(),
        a = T.useRef(null),
        l = T.useRef({
            width: 0,
            height: 0,
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
        }),
        { nonce: c } = T.useContext(Cn),
        u = t.props?.ref ?? t?.ref,
        h = yc(a, u);
    return (
        T.useInsertionEffect(() => {
            const {
                width: f,
                height: d,
                top: m,
                left: y,
                right: p,
                bottom: g,
            } = l.current;
            if (e || r === !1 || !a.current || !f || !d) return;
            const x = n === 'left' ? `left: ${y}` : `right: ${p}`,
                v = s === 'bottom' ? `bottom: ${g}` : `top: ${m}`;
            a.current.dataset.motionPopId = o;
            const S = document.createElement('style');
            c && (S.nonce = c);
            const w = i ?? document.head;
            return (
                w.appendChild(S),
                S.sheet &&
                    S.sheet.insertRule(`
          [data-motion-pop-id="${o}"] {
            position: absolute !important;
            width: ${f}px !important;
            height: ${d}px !important;
            ${x}px !important;
            ${v}px !important;
          }
        `),
                () => {
                    w.contains(S) && w.removeChild(S);
                }
            );
        }, [e]),
        C.jsx(vc, {
            isPresent: e,
            childRef: a,
            sizeRef: l,
            pop: r,
            children: r === !1 ? t : T.cloneElement(t, { ref: h }),
        })
    );
}
const Tc = ({
    children: t,
    initial: e,
    isPresent: n,
    onExitComplete: s,
    custom: i,
    presenceAffectsLayout: r,
    mode: o,
    anchorX: a,
    anchorY: l,
    root: c,
}) => {
    const u = Qe(wc),
        h = T.useId();
    let f = !0,
        d = T.useMemo(
            () => (
                (f = !1),
                {
                    id: h,
                    initial: e,
                    isPresent: n,
                    custom: i,
                    onExitComplete: (m) => {
                        u.set(m, !0);
                        for (const y of u.values()) if (!y) return;
                        s && s();
                    },
                    register: (m) => (u.set(m, !1), () => u.delete(m)),
                }
            ),
            [n, u, s],
        );
    return (
        r && f && (d = { ...d }),
        T.useMemo(() => {
            u.forEach((m, y) => u.set(y, !1));
        }, [n]),
        T.useEffect(() => {
            !n && !u.size && s && s();
        }, [n]),
        (t = C.jsx(xc, {
            pop: o === 'popLayout',
            isPresent: n,
            anchorX: a,
            anchorY: l,
            root: c,
            children: t,
        })),
        C.jsx(ae.Provider, { value: d, children: t })
    );
};
function wc() {
    return new Map();
}
function To(t = !0) {
    const e = T.useContext(ae);
    if (e === null) return [!0, null];
    const { isPresent: n, onExitComplete: s, register: i } = e,
        r = T.useId();
    T.useEffect(() => {
        if (t) return i(r);
    }, [t]);
    const o = T.useCallback(() => t && s && s(r), [r, s, t]);
    return !n && s ? [!1, o] : [!0];
}
const Kt = (t) => t.key || '';
function Ls(t) {
    const e = [];
    return (
        T.Children.forEach(t, (n) => {
            T.isValidElement(n) && e.push(n);
        }),
        e
    );
}
const Pc = ({
        children: t,
        custom: e,
        initial: n = !0,
        onExitComplete: s,
        presenceAffectsLayout: i = !0,
        mode: r = 'sync',
        propagate: o = !1,
        anchorX: a = 'left',
        anchorY: l = 'top',
        root: c,
    }) => {
        const [u, h] = To(o),
            f = T.useMemo(() => Ls(t), [t]),
            d = o && !u ? [] : f.map(Kt),
            m = T.useRef(!0),
            y = T.useRef(f),
            p = Qe(() => new Map()),
            g = T.useRef(new Set()),
            [x, v] = T.useState(f),
            [S, w] = T.useState(f);
        _s(() => {
            ((m.current = !1), (y.current = f));
            for (let b = 0; b < S.length; b++) {
                const V = Kt(S[b]);
                d.includes(V)
                    ? (p.delete(V), g.current.delete(V))
                    : p.get(V) !== !0 && p.set(V, !1);
            }
        }, [S, d.length, d.join('-')]);
        const A = [];
        if (f !== x) {
            let b = [...f];
            for (let V = 0; V < S.length; V++) {
                const E = S[V],
                    N = Kt(E);
                d.includes(N) || (b.splice(V, 0, E), A.push(E));
            }
            return (r === 'wait' && A.length && (b = A), w(Ls(b)), v(f), null);
        }
        const { forceRender: M } = T.useContext(Je);
        return C.jsx(C.Fragment, {
            children: S.map((b) => {
                const V = Kt(b),
                    E = o && !u ? !1 : f === S || d.includes(V),
                    N = () => {
                        if (g.current.has(V)) return;
                        if ((g.current.add(V), p.has(V))) p.set(V, !0);
                        else return;
                        let Y = !0;
                        (p.forEach((st) => {
                            st || (Y = !1);
                        }),
                            Y && (M?.(), w(y.current), o && h?.(), s && s()));
                    };
                return C.jsx(
                    Tc,
                    {
                        isPresent: E,
                        initial: !m.current || n ? void 0 : !1,
                        custom: e,
                        presenceAffectsLayout: i,
                        mode: r,
                        root: c,
                        onExitComplete: E ? void 0 : N,
                        anchorX: a,
                        anchorY: l,
                        children: b,
                    },
                    V,
                );
            }),
        });
    },
    wo = T.createContext({ strict: !1 }),
    ks = {
        animation: [
            'animate',
            'variants',
            'whileHover',
            'whileTap',
            'exit',
            'whileInView',
            'whileFocus',
            'whileDrag',
        ],
        exit: ['exit'],
        drag: ['drag', 'dragControls'],
        focus: ['whileFocus'],
        hover: ['whileHover', 'onHoverStart', 'onHoverEnd'],
        tap: ['whileTap', 'onTap', 'onTapStart', 'onTapCancel'],
        pan: ['onPan', 'onPanStart', 'onPanSessionStart', 'onPanEnd'],
        inView: ['whileInView', 'onViewportEnter', 'onViewportLeave'],
        layout: ['layout', 'layoutId'],
    };
let Is = !1;
function Sc() {
    if (Is) return;
    const t = {};
    for (const e in ks) t[e] = { isEnabled: (n) => ks[e].some((s) => !!n[s]) };
    (Yi(t), (Is = !0));
}
function Po() {
    return (Sc(), ll());
}
function bc(t) {
    const e = Po();
    for (const n in t) e[n] = { ...e[n], ...t[n] };
    Yi(e);
}
const Ac = new Set([
    'animate',
    'exit',
    'variants',
    'initial',
    'style',
    'values',
    'variants',
    'transition',
    'transformTemplate',
    'custom',
    'inherit',
    'onBeforeLayoutMeasure',
    'onAnimationStart',
    'onAnimationComplete',
    'onUpdate',
    'onDragStart',
    'onDrag',
    'onDragEnd',
    'onMeasureDragConstraints',
    'onDirectionLock',
    'onDragTransitionEnd',
    '_dragX',
    '_dragY',
    'onHoverStart',
    'onHoverEnd',
    'onViewportEnter',
    'onViewportLeave',
    'globalTapTarget',
    'propagate',
    'ignoreStrict',
    'viewport',
]);
function re(t) {
    return (
        t.startsWith('while') ||
        (t.startsWith('drag') && t !== 'draggable') ||
        t.startsWith('layout') ||
        t.startsWith('onTap') ||
        t.startsWith('onPan') ||
        t.startsWith('onLayout') ||
        Ac.has(t)
    );
}
let So = (t) => !re(t);
function Vc(t) {
    typeof t == 'function' &&
        (So = (e) => (e.startsWith('on') ? !re(e) : t(e)));
}
try {
    Vc(require('@emotion/is-prop-valid').default);
} catch {}
function Cc(t, e, n) {
    const s = {};
    for (const i in t)
        (i === 'values' && typeof t.values == 'object') ||
            ((So(i) ||
                (n === !0 && re(i)) ||
                (!e && !re(i)) ||
                (t.draggable && i.startsWith('onDrag'))) &&
                (s[i] = t[i]));
    return s;
}
const ue = T.createContext({});
function Mc(t, e) {
    if (ce(t)) {
        const { initial: n, animate: s } = t;
        return {
            initial: n === !1 || It(n) ? n : void 0,
            animate: It(s) ? s : void 0,
        };
    }
    return t.inherit !== !1 ? e : {};
}
function Dc(t) {
    const { initial: e, animate: n } = Mc(t, T.useContext(ue));
    return T.useMemo(() => ({ initial: e, animate: n }), [Fs(e), Fs(n)]);
}
function Fs(t) {
    return Array.isArray(t) ? t.join(' ') : t;
}
const Mn = () => ({ style: {}, transform: {}, transformOrigin: {}, vars: {} });
function bo(t, e, n) {
    for (const s in e) !O(e[s]) && !no(s, n) && (t[s] = e[s]);
}
function Ec({ transformTemplate: t }, e) {
    return T.useMemo(() => {
        const n = Mn();
        return (An(n, e, t), Object.assign({}, n.vars, n.style));
    }, [e]);
}
function Rc(t, e) {
    const n = t.style || {},
        s = {};
    return (bo(s, n, t), Object.assign(s, Ec(t, e)), s);
}
function Lc(t, e) {
    const n = {},
        s = Rc(t, e);
    return (
        t.drag &&
            t.dragListener !== !1 &&
            ((n.draggable = !1),
            (s.userSelect = s.WebkitUserSelect = s.WebkitTouchCallout = 'none'),
            (s.touchAction =
                t.drag === !0 ? 'none' : `pan-${t.drag === 'x' ? 'y' : 'x'}`)),
        t.tabIndex === void 0 &&
            (t.onTap || t.onTapStart || t.whileTap) &&
            (n.tabIndex = 0),
        (n.style = s),
        n
    );
}
const Ao = () => ({ ...Mn(), attrs: {} });
function kc(t, e, n, s) {
    const i = T.useMemo(() => {
        const r = Ao();
        return (
            so(r, e, oo(s), t.transformTemplate, t.style),
            { ...r.attrs, style: { ...r.style } }
        );
    }, [e]);
    if (t.style) {
        const r = {};
        (bo(r, t.style, t), (i.style = { ...r, ...i.style }));
    }
    return i;
}
const Ic = [
    'animate',
    'circle',
    'defs',
    'desc',
    'ellipse',
    'g',
    'image',
    'line',
    'filter',
    'marker',
    'mask',
    'metadata',
    'path',
    'pattern',
    'polygon',
    'polyline',
    'rect',
    'stop',
    'switch',
    'symbol',
    'svg',
    'text',
    'tspan',
    'use',
    'view',
];
function Dn(t) {
    return typeof t != 'string' || t.includes('-')
        ? !1
        : !!(Ic.indexOf(t) > -1 || /[A-Z]/u.test(t));
}
function Fc(t, e, n, { latestValues: s }, i, r = !1, o) {
    const l = ((o ?? Dn(t)) ? kc : Lc)(e, s, i, t),
        c = Cc(e, typeof t == 'string', r),
        u = t !== T.Fragment ? { ...c, ...l, ref: n } : {},
        { children: h } = e,
        f = T.useMemo(() => (O(h) ? h.get() : h), [h]);
    return T.createElement(t, { ...u, children: f });
}
function jc({ scrapeMotionValuesFromProps: t, createRenderState: e }, n, s, i) {
    return { latestValues: Bc(n, s, i, t), renderState: e() };
}
function Bc(t, e, n, s) {
    const i = {},
        r = s(t, {});
    for (const f in r) i[f] = qt(r[f]);
    let { initial: o, animate: a } = t;
    const l = ce(t),
        c = _i(t);
    e &&
        c &&
        !l &&
        t.inherit !== !1 &&
        (o === void 0 && (o = e.initial), a === void 0 && (a = e.animate));
    let u = n ? n.initial === !1 : !1;
    u = u || o === !1;
    const h = u ? a : o;
    if (h && typeof h != 'boolean' && !le(h)) {
        const f = Array.isArray(h) ? h : [h];
        for (let d = 0; d < f.length; d++) {
            const m = yn(t, f[d]);
            if (m) {
                const { transitionEnd: y, transition: p, ...g } = m;
                for (const x in g) {
                    let v = g[x];
                    if (Array.isArray(v)) {
                        const S = u ? v.length - 1 : 0;
                        v = v[S];
                    }
                    v !== null && (i[x] = v);
                }
                for (const x in y) i[x] = y[x];
            }
        }
    }
    return i;
}
const Vo = (t) => (e, n) => {
        const s = T.useContext(ue),
            i = T.useContext(ae),
            r = () => jc(t, e, s, i);
        return n ? r() : Qe(r);
    },
    Oc = Vo({ scrapeMotionValuesFromProps: Vn, createRenderState: Mn }),
    Nc = Vo({ scrapeMotionValuesFromProps: ro, createRenderState: Ao }),
    Uc = Symbol.for('motionComponentSymbol');
function Wc(t, e, n) {
    const s = T.useRef(n);
    T.useInsertionEffect(() => {
        s.current = n;
    });
    const i = T.useRef(null);
    return T.useCallback(
        (r) => {
            (r && t.onMount?.(r), e && (r ? e.mount(r) : e.unmount()));
            const o = s.current;
            if (typeof o == 'function')
                if (r) {
                    const a = o(r);
                    typeof a == 'function' && (i.current = a);
                } else i.current ? (i.current(), (i.current = null)) : o(r);
            else o && (o.current = r);
        },
        [e],
    );
}
const Co = T.createContext({});
function mt(t) {
    return (
        t &&
        typeof t == 'object' &&
        Object.prototype.hasOwnProperty.call(t, 'current')
    );
}
function Kc(t, e, n, s, i, r) {
    const { visualElement: o } = T.useContext(ue),
        a = T.useContext(wo),
        l = T.useContext(ae),
        c = T.useContext(Cn),
        u = c.reducedMotion,
        h = c.skipAnimations,
        f = T.useRef(null),
        d = T.useRef(!1);
    ((s = s || a.renderer),
        !f.current &&
            s &&
            ((f.current = s(t, {
                visualState: e,
                parent: o,
                props: n,
                presenceContext: l,
                blockInitialAnimation: l ? l.initial === !1 : !1,
                reducedMotionConfig: u,
                skipAnimations: h,
                isSVG: r,
            })),
            d.current && f.current && (f.current.manuallyAnimateOnMount = !0)));
    const m = f.current,
        y = T.useContext(Co);
    m &&
        !m.projection &&
        i &&
        (m.type === 'html' || m.type === 'svg') &&
        $c(f.current, n, i, y);
    const p = T.useRef(!1);
    T.useInsertionEffect(() => {
        m && p.current && m.update(n, l);
    });
    const g = n[Ii],
        x = T.useRef(
            !!g &&
                !window.MotionHandoffIsComplete?.(g) &&
                window.MotionHasOptimisedAnimation?.(g),
        );
    return (
        _s(() => {
            ((d.current = !0),
                m &&
                    ((p.current = !0),
                    (window.MotionIsMounted = !0),
                    m.updateFeatures(),
                    m.scheduleRenderMicrotask(),
                    x.current &&
                        m.animationState &&
                        m.animationState.animateChanges()));
        }),
        T.useEffect(() => {
            m &&
                (!x.current &&
                    m.animationState &&
                    m.animationState.animateChanges(),
                x.current &&
                    (queueMicrotask(() => {
                        window.MotionHandoffMarkAsComplete?.(g);
                    }),
                    (x.current = !1)),
                (m.enteringChildren = void 0));
        }),
        m
    );
}
function $c(t, e, n, s) {
    const {
        layoutId: i,
        layout: r,
        drag: o,
        dragConstraints: a,
        layoutScroll: l,
        layoutRoot: c,
        layoutCrossfade: u,
    } = e;
    ((t.projection = new n(
        t.latestValues,
        e['data-framer-portal-id'] ? void 0 : Mo(t.parent),
    )),
        t.projection.setOptions({
            layoutId: i,
            layout: r,
            alwaysMeasureLayout: !!o || (a && mt(a)),
            visualElement: t,
            animationType: typeof r == 'string' ? r : 'both',
            initialPromotionConfig: s,
            crossfade: u,
            layoutScroll: l,
            layoutRoot: c,
        }));
}
function Mo(t) {
    if (t)
        return t.options.allowProjection !== !1 ? t.projection : Mo(t.parent);
}
function Pe(t, { forwardMotionProps: e = !1, type: n } = {}, s, i) {
    s && bc(s);
    const r = n ? n === 'svg' : Dn(t),
        o = r ? Nc : Oc;
    function a(c, u) {
        let h;
        const f = { ...T.useContext(Cn), ...c, layoutId: Hc(c) },
            { isStatic: d } = f,
            m = Dc(c),
            y = o(c, d);
        if (!d && Gs) {
            zc();
            const p = Gc(f);
            ((h = p.MeasureLayout),
                (m.visualElement = Kc(t, y, f, i, p.ProjectionNode, r)));
        }
        return C.jsxs(ue.Provider, {
            value: m,
            children: [
                h && m.visualElement
                    ? C.jsx(h, { visualElement: m.visualElement, ...f })
                    : null,
                Fc(t, c, Wc(y, m.visualElement, u), y, d, e, r),
            ],
        });
    }
    a.displayName = `motion.${typeof t == 'string' ? t : `create(${t.displayName ?? t.name ?? ''})`}`;
    const l = T.forwardRef(a);
    return ((l[Uc] = t), l);
}
function Hc({ layoutId: t }) {
    const e = T.useContext(Je).id;
    return e && t !== void 0 ? e + '-' + t : t;
}
function zc(t, e) {
    T.useContext(wo).strict;
}
function Gc(t) {
    const e = Po(),
        { drag: n, layout: s } = e;
    if (!n && !s) return {};
    const i = { ...n, ...s };
    return {
        MeasureLayout:
            n?.isEnabled(t) || s?.isEnabled(t) ? i.MeasureLayout : void 0,
        ProjectionNode: i.ProjectionNode,
    };
}
function _c(t, e) {
    if (typeof Proxy > 'u') return Pe;
    const n = new Map(),
        s = (r, o) => Pe(r, o, t, e),
        i = (r, o) => s(r, o);
    return new Proxy(i, {
        get: (r, o) =>
            o === 'create'
                ? s
                : (n.has(o) || n.set(o, Pe(o, void 0, t, e)), n.get(o)),
    });
}
const Xc = (t, e) =>
    (e.isSVG ?? Dn(t))
        ? new Al(e)
        : new xl(e, { allowProjection: t !== T.Fragment });
class Yc extends at {
    constructor(e) {
        (super(e), e.animationState || (e.animationState = El(e)));
    }
    updateAnimationControlsSubscription() {
        const { animate: e } = this.node.getProps();
        le(e) && (this.unmountControls = e.subscribe(this.node));
    }
    mount() {
        this.updateAnimationControlsSubscription();
    }
    update() {
        const { animate: e } = this.node.getProps(),
            { animate: n } = this.node.prevProps || {};
        e !== n && this.updateAnimationControlsSubscription();
    }
    unmount() {
        (this.node.animationState.reset(), this.unmountControls?.());
    }
}
let qc = 0;
class Zc extends at {
    constructor() {
        (super(...arguments), (this.id = qc++));
    }
    update() {
        if (!this.node.presenceContext) return;
        const { isPresent: e, onExitComplete: n } = this.node.presenceContext,
            { isPresent: s } = this.node.prevPresenceContext || {};
        if (!this.node.animationState || e === s) return;
        const i = this.node.animationState.setActive('exit', !e);
        n &&
            !e &&
            i.then(() => {
                n(this.id);
            });
    }
    mount() {
        const { register: e, onExitComplete: n } =
            this.node.presenceContext || {};
        (n && n(this.id), e && (this.unmount = e(this.id)));
    }
    unmount() {}
}
const Jc = { animation: { Feature: Yc }, exit: { Feature: Zc } };
function Nt(t) {
    return { point: { x: t.pageX, y: t.pageY } };
}
const Qc = (t) => (e) => wn(e) && t(e, Nt(e));
function Et(t, e, n, s) {
    return Ft(t, e, Qc(n), s);
}
const Do = ({ current: t }) => (t ? t.ownerDocument.defaultView : null),
    js = (t, e) => Math.abs(t - e);
function tu(t, e) {
    const n = js(t.x, e.x),
        s = js(t.y, e.y);
    return Math.sqrt(n ** 2 + s ** 2);
}
const Bs = new Set(['auto', 'scroll']);
class Eo {
    constructor(
        e,
        n,
        {
            transformPagePoint: s,
            contextWindow: i = window,
            dragSnapToOrigin: r = !1,
            distanceThreshold: o = 3,
            element: a,
        } = {},
    ) {
        if (
            ((this.startEvent = null),
            (this.lastMoveEvent = null),
            (this.lastMoveEventInfo = null),
            (this.handlers = {}),
            (this.contextWindow = window),
            (this.scrollPositions = new Map()),
            (this.removeScrollListeners = null),
            (this.onElementScroll = (d) => {
                this.handleScroll(d.target);
            }),
            (this.onWindowScroll = () => {
                this.handleScroll(window);
            }),
            (this.updatePoint = () => {
                if (!(this.lastMoveEvent && this.lastMoveEventInfo)) return;
                const d = be(this.lastMoveEventInfo, this.history),
                    m = this.startEvent !== null,
                    y = tu(d.offset, { x: 0, y: 0 }) >= this.distanceThreshold;
                if (!m && !y) return;
                const { point: p } = d,
                    { timestamp: g } = B;
                this.history.push({ ...p, timestamp: g });
                const { onStart: x, onMove: v } = this.handlers;
                (m ||
                    (x && x(this.lastMoveEvent, d),
                    (this.startEvent = this.lastMoveEvent)),
                    v && v(this.lastMoveEvent, d));
            }),
            (this.handlePointerMove = (d, m) => {
                ((this.lastMoveEvent = d),
                    (this.lastMoveEventInfo = Se(m, this.transformPagePoint)),
                    D.update(this.updatePoint, !0));
            }),
            (this.handlePointerUp = (d, m) => {
                this.end();
                const {
                    onEnd: y,
                    onSessionEnd: p,
                    resumeAnimation: g,
                } = this.handlers;
                if (
                    ((this.dragSnapToOrigin || !this.startEvent) && g && g(),
                    !(this.lastMoveEvent && this.lastMoveEventInfo))
                )
                    return;
                const x = be(
                    d.type === 'pointercancel'
                        ? this.lastMoveEventInfo
                        : Se(m, this.transformPagePoint),
                    this.history,
                );
                (this.startEvent && y && y(d, x), p && p(d, x));
            }),
            !wn(e))
        )
            return;
        ((this.dragSnapToOrigin = r),
            (this.handlers = n),
            (this.transformPagePoint = s),
            (this.distanceThreshold = o),
            (this.contextWindow = i || window));
        const l = Nt(e),
            c = Se(l, this.transformPagePoint),
            { point: u } = c,
            { timestamp: h } = B;
        this.history = [{ ...u, timestamp: h }];
        const { onSessionStart: f } = n;
        (f && f(e, be(c, this.history)),
            (this.removeListeners = jt(
                Et(this.contextWindow, 'pointermove', this.handlePointerMove),
                Et(this.contextWindow, 'pointerup', this.handlePointerUp),
                Et(this.contextWindow, 'pointercancel', this.handlePointerUp),
            )),
            a && this.startScrollTracking(a));
    }
    startScrollTracking(e) {
        let n = e.parentElement;
        for (; n; ) {
            const s = getComputedStyle(n);
            ((Bs.has(s.overflowX) || Bs.has(s.overflowY)) &&
                this.scrollPositions.set(n, {
                    x: n.scrollLeft,
                    y: n.scrollTop,
                }),
                (n = n.parentElement));
        }
        (this.scrollPositions.set(window, {
            x: window.scrollX,
            y: window.scrollY,
        }),
            window.addEventListener('scroll', this.onElementScroll, {
                capture: !0,
            }),
            window.addEventListener('scroll', this.onWindowScroll),
            (this.removeScrollListeners = () => {
                (window.removeEventListener('scroll', this.onElementScroll, {
                    capture: !0,
                }),
                    window.removeEventListener('scroll', this.onWindowScroll));
            }));
    }
    handleScroll(e) {
        const n = this.scrollPositions.get(e);
        if (!n) return;
        const s = e === window,
            i = s
                ? { x: window.scrollX, y: window.scrollY }
                : { x: e.scrollLeft, y: e.scrollTop },
            r = { x: i.x - n.x, y: i.y - n.y };
        (r.x === 0 && r.y === 0) ||
            (s
                ? this.lastMoveEventInfo &&
                  ((this.lastMoveEventInfo.point.x += r.x),
                  (this.lastMoveEventInfo.point.y += r.y))
                : this.history.length > 0 &&
                  ((this.history[0].x -= r.x), (this.history[0].y -= r.y)),
            this.scrollPositions.set(e, i),
            D.update(this.updatePoint, !0));
    }
    updateHandlers(e) {
        this.handlers = e;
    }
    end() {
        (this.removeListeners && this.removeListeners(),
            this.removeScrollListeners && this.removeScrollListeners(),
            this.scrollPositions.clear(),
            rt(this.updatePoint));
    }
}
function Se(t, e) {
    return e ? { point: e(t.point) } : t;
}
function Os(t, e) {
    return { x: t.x - e.x, y: t.y - e.y };
}
function be({ point: t }, e) {
    return {
        point: t,
        delta: Os(t, Ro(e)),
        offset: Os(t, eu(e)),
        velocity: nu(e, 0.1),
    };
}
function eu(t) {
    return t[0];
}
function Ro(t) {
    return t[t.length - 1];
}
function nu(t, e) {
    if (t.length < 2) return { x: 0, y: 0 };
    let n = t.length - 1,
        s = null;
    const i = Ro(t);
    for (; n >= 0 && ((s = t[n]), !(i.timestamp - s.timestamp > _(e))); ) n--;
    if (!s) return { x: 0, y: 0 };
    s === t[0] &&
        t.length > 2 &&
        i.timestamp - s.timestamp > _(e) * 2 &&
        (s = t[1]);
    const r = K(i.timestamp - s.timestamp);
    if (r === 0) return { x: 0, y: 0 };
    const o = { x: (i.x - s.x) / r, y: (i.y - s.y) / r };
    return (o.x === 1 / 0 && (o.x = 0), o.y === 1 / 0 && (o.y = 0), o);
}
function su(t, { min: e, max: n }, s) {
    return (
        e !== void 0 && t < e
            ? (t = s ? L(e, t, s.min) : Math.max(t, e))
            : n !== void 0 &&
              t > n &&
              (t = s ? L(n, t, s.max) : Math.min(t, n)),
        t
    );
}
function Ns(t, e, n) {
    return {
        min: e !== void 0 ? t.min + e : void 0,
        max: n !== void 0 ? t.max + n - (t.max - t.min) : void 0,
    };
}
function iu(t, { top: e, left: n, bottom: s, right: i }) {
    return { x: Ns(t.x, n, i), y: Ns(t.y, e, s) };
}
function Us(t, e) {
    let n = e.min - t.min,
        s = e.max - t.max;
    return (
        e.max - e.min < t.max - t.min && ([n, s] = [s, n]),
        { min: n, max: s }
    );
}
function ou(t, e) {
    return { x: Us(t.x, e.x), y: Us(t.y, e.y) };
}
function ru(t, e) {
    let n = 0.5;
    const s = W(t),
        i = W(e);
    return (
        i > s
            ? (n = Rt(e.min, e.max - s, t.min))
            : s > i && (n = Rt(t.min, t.max - i, e.min)),
        Q(0, 1, n)
    );
}
function au(t, e) {
    const n = {};
    return (
        e.min !== void 0 && (n.min = e.min - t.min),
        e.max !== void 0 && (n.max = e.max - t.min),
        n
    );
}
const qe = 0.35;
function lu(t = qe) {
    return (
        t === !1 ? (t = 0) : t === !0 && (t = qe),
        { x: Ws(t, 'left', 'right'), y: Ws(t, 'top', 'bottom') }
    );
}
function Ws(t, e, n) {
    return { min: Ks(t, e), max: Ks(t, n) };
}
function Ks(t, e) {
    return typeof t == 'number' ? t : t[e] || 0;
}
const cu = new WeakMap();
class uu {
    constructor(e) {
        ((this.openDragLock = null),
            (this.isDragging = !1),
            (this.currentDirection = null),
            (this.originPoint = { x: 0, y: 0 }),
            (this.constraints = !1),
            (this.hasMutatedConstraints = !1),
            (this.elastic = F()),
            (this.latestPointerEvent = null),
            (this.latestPanInfo = null),
            (this.visualElement = e));
    }
    start(e, { snapToCursor: n = !1, distanceThreshold: s } = {}) {
        const { presenceContext: i } = this.visualElement;
        if (i && i.isPresent === !1) return;
        const r = (h) => {
                (n && this.snapToCursor(Nt(h).point), this.stopAnimation());
            },
            o = (h, f) => {
                const {
                    drag: d,
                    dragPropagation: m,
                    onDragStart: y,
                } = this.getProps();
                if (
                    d &&
                    !m &&
                    (this.openDragLock && this.openDragLock(),
                    (this.openDragLock = Oa(d)),
                    !this.openDragLock)
                )
                    return;
                ((this.latestPointerEvent = h),
                    (this.latestPanInfo = f),
                    (this.isDragging = !0),
                    (this.currentDirection = null),
                    this.resolveConstraints(),
                    this.visualElement.projection &&
                        ((this.visualElement.projection.isAnimationBlocked =
                            !0),
                        (this.visualElement.projection.target = void 0)),
                    Z((g) => {
                        let x = this.getAxisMotionValue(g).get() || 0;
                        if (J.test(x)) {
                            const { projection: v } = this.visualElement;
                            if (v && v.layout) {
                                const S = v.layout.layoutBox[g];
                                S && (x = W(S) * (parseFloat(x) / 100));
                            }
                        }
                        this.originPoint[g] = x;
                    }),
                    y && D.update(() => y(h, f), !1, !0),
                    We(this.visualElement, 'transform'));
                const { animationState: p } = this.visualElement;
                p && p.setActive('whileDrag', !0);
            },
            a = (h, f) => {
                ((this.latestPointerEvent = h), (this.latestPanInfo = f));
                const {
                    dragPropagation: d,
                    dragDirectionLock: m,
                    onDirectionLock: y,
                    onDrag: p,
                } = this.getProps();
                if (!d && !this.openDragLock) return;
                const { offset: g } = f;
                if (m && this.currentDirection === null) {
                    ((this.currentDirection = fu(g)),
                        this.currentDirection !== null &&
                            y &&
                            y(this.currentDirection));
                    return;
                }
                (this.updateAxis('x', f.point, g),
                    this.updateAxis('y', f.point, g),
                    this.visualElement.render(),
                    p && D.update(() => p(h, f), !1, !0));
            },
            l = (h, f) => {
                ((this.latestPointerEvent = h),
                    (this.latestPanInfo = f),
                    this.stop(h, f),
                    (this.latestPointerEvent = null),
                    (this.latestPanInfo = null));
            },
            c = () => {
                const { dragSnapToOrigin: h } = this.getProps();
                (h || this.constraints) && this.startAnimation({ x: 0, y: 0 });
            },
            { dragSnapToOrigin: u } = this.getProps();
        this.panSession = new Eo(
            e,
            {
                onSessionStart: r,
                onStart: o,
                onMove: a,
                onSessionEnd: l,
                resumeAnimation: c,
            },
            {
                transformPagePoint: this.visualElement.getTransformPagePoint(),
                dragSnapToOrigin: u,
                distanceThreshold: s,
                contextWindow: Do(this.visualElement),
                element: this.visualElement.current,
            },
        );
    }
    stop(e, n) {
        const s = e || this.latestPointerEvent,
            i = n || this.latestPanInfo,
            r = this.isDragging;
        if ((this.cancel(), !r || !i || !s)) return;
        const { velocity: o } = i;
        this.startAnimation(o);
        const { onDragEnd: a } = this.getProps();
        a && D.postRender(() => a(s, i));
    }
    cancel() {
        this.isDragging = !1;
        const { projection: e, animationState: n } = this.visualElement;
        (e && (e.isAnimationBlocked = !1), this.endPanSession());
        const { dragPropagation: s } = this.getProps();
        (!s &&
            this.openDragLock &&
            (this.openDragLock(), (this.openDragLock = null)),
            n && n.setActive('whileDrag', !1));
    }
    endPanSession() {
        (this.panSession && this.panSession.end(), (this.panSession = void 0));
    }
    updateAxis(e, n, s) {
        const { drag: i } = this.getProps();
        if (!s || !$t(e, i, this.currentDirection)) return;
        const r = this.getAxisMotionValue(e);
        let o = this.originPoint[e] + s[e];
        (this.constraints &&
            this.constraints[e] &&
            (o = su(o, this.constraints[e], this.elastic[e])),
            r.set(o));
    }
    resolveConstraints() {
        const { dragConstraints: e, dragElastic: n } = this.getProps(),
            s =
                this.visualElement.projection &&
                !this.visualElement.projection.layout
                    ? this.visualElement.projection.measure(!1)
                    : this.visualElement.projection?.layout,
            i = this.constraints;
        (e && mt(e)
            ? this.constraints ||
              (this.constraints = this.resolveRefConstraints())
            : e && s
              ? (this.constraints = iu(s.layoutBox, e))
              : (this.constraints = !1),
            (this.elastic = lu(n)),
            i !== this.constraints &&
                !mt(e) &&
                s &&
                this.constraints &&
                !this.hasMutatedConstraints &&
                Z((r) => {
                    this.constraints !== !1 &&
                        this.getAxisMotionValue(r) &&
                        (this.constraints[r] = au(
                            s.layoutBox[r],
                            this.constraints[r],
                        ));
                }));
    }
    resolveRefConstraints() {
        const { dragConstraints: e, onMeasureDragConstraints: n } =
            this.getProps();
        if (!e || !mt(e)) return !1;
        const s = e.current,
            { projection: i } = this.visualElement;
        if (!i || !i.layout) return !1;
        const r = dl(s, i.root, this.visualElement.getTransformPagePoint());
        let o = ou(i.layout.layoutBox, r);
        if (n) {
            const a = n(ul(o));
            ((this.hasMutatedConstraints = !!a), a && (o = Zi(a)));
        }
        return o;
    }
    startAnimation(e) {
        const {
                drag: n,
                dragMomentum: s,
                dragElastic: i,
                dragTransition: r,
                dragSnapToOrigin: o,
                onDragTransitionEnd: a,
            } = this.getProps(),
            l = this.constraints || {},
            c = Z((u) => {
                if (!$t(u, n, this.currentDirection)) return;
                let h = (l && l[u]) || {};
                o && (h = { min: 0, max: 0 });
                const f = i ? 200 : 1e6,
                    d = i ? 40 : 1e7,
                    m = {
                        type: 'inertia',
                        velocity: s ? e[u] : 0,
                        bounceStiffness: f,
                        bounceDamping: d,
                        timeConstant: 750,
                        restDelta: 1,
                        restSpeed: 10,
                        ...r,
                        ...h,
                    };
                return this.startAxisValueAnimation(u, m);
            });
        return Promise.all(c).then(a);
    }
    startAxisValueAnimation(e, n) {
        const s = this.getAxisMotionValue(e);
        return (
            We(this.visualElement, e),
            s.start(gn(e, s, 0, n, this.visualElement, !1))
        );
    }
    stopAnimation() {
        Z((e) => this.getAxisMotionValue(e).stop());
    }
    getAxisMotionValue(e) {
        const n = `_drag${e.toUpperCase()}`,
            s = this.visualElement.getProps(),
            i = s[n];
        return (
            i ||
            this.visualElement.getValue(
                e,
                (s.initial ? s.initial[e] : void 0) || 0,
            )
        );
    }
    snapToCursor(e) {
        Z((n) => {
            const { drag: s } = this.getProps();
            if (!$t(n, s, this.currentDirection)) return;
            const { projection: i } = this.visualElement,
                r = this.getAxisMotionValue(n);
            if (i && i.layout) {
                const { min: o, max: a } = i.layout.layoutBox[n],
                    l = r.get() || 0;
                r.set(e[n] - L(o, a, 0.5) + l);
            }
        });
    }
    scalePositionWithinConstraints() {
        if (!this.visualElement.current) return;
        const { drag: e, dragConstraints: n } = this.getProps(),
            { projection: s } = this.visualElement;
        if (!mt(n) || !s || !this.constraints) return;
        this.stopAnimation();
        const i = { x: 0, y: 0 };
        Z((o) => {
            const a = this.getAxisMotionValue(o);
            if (a && this.constraints !== !1) {
                const l = a.get();
                i[o] = ru({ min: l, max: l }, this.constraints[o]);
            }
        });
        const { transformTemplate: r } = this.visualElement.getProps();
        ((this.visualElement.current.style.transform = r ? r({}, '') : 'none'),
            s.root && s.root.updateScroll(),
            s.updateLayout(),
            (this.constraints = !1),
            this.resolveConstraints(),
            Z((o) => {
                if (!$t(o, e, null)) return;
                const a = this.getAxisMotionValue(o),
                    { min: l, max: c } = this.constraints[o];
                a.set(L(l, c, i[o]));
            }),
            this.visualElement.render());
    }
    addListeners() {
        if (!this.visualElement.current) return;
        cu.set(this.visualElement, this);
        const e = this.visualElement.current,
            n = Et(e, 'pointerdown', (c) => {
                const { drag: u, dragListener: h = !0 } = this.getProps(),
                    f = c.target,
                    d = f !== e && Ha(f);
                u && h && !d && this.start(c);
            });
        let s;
        const i = () => {
                const { dragConstraints: c } = this.getProps();
                mt(c) &&
                    c.current &&
                    ((this.constraints = this.resolveRefConstraints()),
                    s ||
                        (s = hu(e, c.current, () =>
                            this.scalePositionWithinConstraints(),
                        )));
            },
            { projection: r } = this.visualElement,
            o = r.addEventListener('measure', i);
        (r && !r.layout && (r.root && r.root.updateScroll(), r.updateLayout()),
            D.read(i));
        const a = Ft(window, 'resize', () =>
                this.scalePositionWithinConstraints(),
            ),
            l = r.addEventListener(
                'didUpdate',
                ({ delta: c, hasLayoutChanged: u }) => {
                    this.isDragging &&
                        u &&
                        (Z((h) => {
                            const f = this.getAxisMotionValue(h);
                            f &&
                                ((this.originPoint[h] += c[h].translate),
                                f.set(f.get() + c[h].translate));
                        }),
                        this.visualElement.render());
                },
            );
        return () => {
            (a(), n(), o(), l && l(), s && s());
        };
    }
    getProps() {
        const e = this.visualElement.getProps(),
            {
                drag: n = !1,
                dragDirectionLock: s = !1,
                dragPropagation: i = !1,
                dragConstraints: r = !1,
                dragElastic: o = qe,
                dragMomentum: a = !0,
            } = e;
        return {
            ...e,
            drag: n,
            dragDirectionLock: s,
            dragPropagation: i,
            dragConstraints: r,
            dragElastic: o,
            dragMomentum: a,
        };
    }
}
function $s(t) {
    let e = !0;
    return () => {
        if (e) {
            e = !1;
            return;
        }
        t();
    };
}
function hu(t, e, n) {
    const s = qn(t, $s(n)),
        i = qn(e, $s(n));
    return () => {
        (s(), i());
    };
}
function $t(t, e, n) {
    return (e === !0 || e === t) && (n === null || n === t);
}
function fu(t, e = 10) {
    let n = null;
    return (Math.abs(t.y) > e ? (n = 'y') : Math.abs(t.x) > e && (n = 'x'), n);
}
class du extends at {
    constructor(e) {
        (super(e),
            (this.removeGroupControls = $),
            (this.removeListeners = $),
            (this.controls = new uu(e)));
    }
    mount() {
        const { dragControls: e } = this.node.getProps();
        (e && (this.removeGroupControls = e.subscribe(this.controls)),
            (this.removeListeners = this.controls.addListeners() || $));
    }
    update() {
        const { dragControls: e } = this.node.getProps(),
            { dragControls: n } = this.node.prevProps || {};
        e !== n &&
            (this.removeGroupControls(),
            e && (this.removeGroupControls = e.subscribe(this.controls)));
    }
    unmount() {
        (this.removeGroupControls(),
            this.removeListeners(),
            this.controls.isDragging || this.controls.endPanSession());
    }
}
const Ae = (t) => (e, n) => {
    t && D.update(() => t(e, n), !1, !0);
};
class mu extends at {
    constructor() {
        (super(...arguments), (this.removePointerDownListener = $));
    }
    onPointerDown(e) {
        this.session = new Eo(e, this.createPanHandlers(), {
            transformPagePoint: this.node.getTransformPagePoint(),
            contextWindow: Do(this.node),
        });
    }
    createPanHandlers() {
        const {
            onPanSessionStart: e,
            onPanStart: n,
            onPan: s,
            onPanEnd: i,
        } = this.node.getProps();
        return {
            onSessionStart: Ae(e),
            onStart: Ae(n),
            onMove: Ae(s),
            onEnd: (r, o) => {
                (delete this.session, i && D.postRender(() => i(r, o)));
            },
        };
    }
    mount() {
        this.removePointerDownListener = Et(
            this.node.current,
            'pointerdown',
            (e) => this.onPointerDown(e),
        );
    }
    update() {
        this.session && this.session.updateHandlers(this.createPanHandlers());
    }
    unmount() {
        (this.removePointerDownListener(), this.session && this.session.end());
    }
}
let Ve = !1;
class pu extends T.Component {
    componentDidMount() {
        const {
                visualElement: e,
                layoutGroup: n,
                switchLayoutGroup: s,
                layoutId: i,
            } = this.props,
            { projection: r } = e;
        (r &&
            (n.group && n.group.add(r),
            s && s.register && i && s.register(r),
            Ve && r.root.didUpdate(),
            r.addEventListener('animationComplete', () => {
                this.safeToRemove();
            }),
            r.setOptions({
                ...r.options,
                layoutDependency: this.props.layoutDependency,
                onExitComplete: () => this.safeToRemove(),
            })),
            (Zt.hasEverUpdated = !0));
    }
    getSnapshotBeforeUpdate(e) {
        const {
                layoutDependency: n,
                visualElement: s,
                drag: i,
                isPresent: r,
            } = this.props,
            { projection: o } = s;
        return (
            o &&
                ((o.isPresent = r),
                e.layoutDependency !== n &&
                    o.setOptions({ ...o.options, layoutDependency: n }),
                (Ve = !0),
                i ||
                e.layoutDependency !== n ||
                n === void 0 ||
                e.isPresent !== r
                    ? o.willUpdate()
                    : this.safeToRemove(),
                e.isPresent !== r &&
                    (r
                        ? o.promote()
                        : o.relegate() ||
                          D.postRender(() => {
                              const a = o.getStack();
                              (!a || !a.members.length) && this.safeToRemove();
                          }))),
            null
        );
    }
    componentDidUpdate() {
        const { projection: e } = this.props.visualElement;
        e &&
            (e.root.didUpdate(),
            Tn.postRender(() => {
                !e.currentAnimation && e.isLead() && this.safeToRemove();
            }));
    }
    componentWillUnmount() {
        const {
                visualElement: e,
                layoutGroup: n,
                switchLayoutGroup: s,
            } = this.props,
            { projection: i } = e;
        ((Ve = !0),
            i &&
                (i.scheduleCheckAfterUnmount(),
                n && n.group && n.group.remove(i),
                s && s.deregister && s.deregister(i)));
    }
    safeToRemove() {
        const { safeToRemove: e } = this.props;
        e && e();
    }
    render() {
        return null;
    }
}
function Lo(t) {
    const [e, n] = To(),
        s = T.useContext(Je);
    return C.jsx(pu, {
        ...t,
        layoutGroup: s,
        switchLayoutGroup: T.useContext(Co),
        isPresent: e,
        safeToRemove: n,
    });
}
const gu = {
    pan: { Feature: mu },
    drag: { Feature: du, ProjectionNode: xo, MeasureLayout: Lo },
};
function Hs(t, e, n) {
    const { props: s } = t;
    t.animationState &&
        s.whileHover &&
        t.animationState.setActive('whileHover', n === 'Start');
    const i = 'onHover' + n,
        r = s[i];
    r && D.postRender(() => r(e, Nt(e)));
}
class yu extends at {
    mount() {
        const { current: e } = this.node;
        e &&
            (this.unmount = Ua(
                e,
                (n, s) => (
                    Hs(this.node, s, 'Start'),
                    (i) => Hs(this.node, i, 'End')
                ),
            ));
    }
    unmount() {}
}
class vu extends at {
    constructor() {
        (super(...arguments), (this.isActive = !1));
    }
    onFocus() {
        let e = !1;
        try {
            e = this.node.current.matches(':focus-visible');
        } catch {
            e = !0;
        }
        !e ||
            !this.node.animationState ||
            (this.node.animationState.setActive('whileFocus', !0),
            (this.isActive = !0));
    }
    onBlur() {
        !this.isActive ||
            !this.node.animationState ||
            (this.node.animationState.setActive('whileFocus', !1),
            (this.isActive = !1));
    }
    mount() {
        this.unmount = jt(
            Ft(this.node.current, 'focus', () => this.onFocus()),
            Ft(this.node.current, 'blur', () => this.onBlur()),
        );
    }
    unmount() {}
}
function zs(t, e, n) {
    const { props: s } = t;
    if (t.current instanceof HTMLButtonElement && t.current.disabled) return;
    t.animationState &&
        s.whileTap &&
        t.animationState.setActive('whileTap', n === 'Start');
    const i = 'onTap' + (n === 'End' ? '' : n),
        r = s[i];
    r && D.postRender(() => r(e, Nt(e)));
}
class xu extends at {
    mount() {
        const { current: e } = this.node;
        if (!e) return;
        const { globalTapTarget: n, propagate: s } = this.node.props;
        this.unmount = Ga(
            e,
            (i, r) => (
                zs(this.node, r, 'Start'),
                (o, { success: a }) => zs(this.node, o, a ? 'End' : 'Cancel')
            ),
            { useGlobalTarget: n, stopPropagation: s?.tap === !1 },
        );
    }
    unmount() {}
}
const Ze = new WeakMap(),
    Ce = new WeakMap(),
    Tu = (t) => {
        const e = Ze.get(t.target);
        e && e(t);
    },
    wu = (t) => {
        t.forEach(Tu);
    };
function Pu({ root: t, ...e }) {
    const n = t || document;
    Ce.has(n) || Ce.set(n, {});
    const s = Ce.get(n),
        i = JSON.stringify(e);
    return (
        s[i] || (s[i] = new IntersectionObserver(wu, { root: t, ...e })),
        s[i]
    );
}
function Su(t, e, n) {
    const s = Pu(e);
    return (
        Ze.set(t, n),
        s.observe(t),
        () => {
            (Ze.delete(t), s.unobserve(t));
        }
    );
}
const bu = { some: 0, all: 1 };
class Au extends at {
    constructor() {
        (super(...arguments), (this.hasEnteredView = !1), (this.isInView = !1));
    }
    startObserver() {
        this.unmount();
        const { viewport: e = {} } = this.node.getProps(),
            { root: n, margin: s, amount: i = 'some', once: r } = e,
            o = {
                root: n ? n.current : void 0,
                rootMargin: s,
                threshold: typeof i == 'number' ? i : bu[i],
            },
            a = (l) => {
                const { isIntersecting: c } = l;
                if (
                    this.isInView === c ||
                    ((this.isInView = c), r && !c && this.hasEnteredView)
                )
                    return;
                (c && (this.hasEnteredView = !0),
                    this.node.animationState &&
                        this.node.animationState.setActive('whileInView', c));
                const { onViewportEnter: u, onViewportLeave: h } =
                        this.node.getProps(),
                    f = c ? u : h;
                f && f(l);
            };
        return Su(this.node.current, o, a);
    }
    mount() {
        this.startObserver();
    }
    update() {
        if (typeof IntersectionObserver > 'u') return;
        const { props: e, prevProps: n } = this.node;
        ['amount', 'margin', 'root'].some(Vu(e, n)) && this.startObserver();
    }
    unmount() {}
}
function Vu({ viewport: t = {} }, { viewport: e = {} } = {}) {
    return (n) => t[n] !== e[n];
}
const Cu = {
        inView: { Feature: Au },
        tap: { Feature: xu },
        focus: { Feature: vu },
        hover: { Feature: yu },
    },
    Mu = { layout: { ProjectionNode: xo, MeasureLayout: Lo } },
    Du = { ...Jc, ...Cu, ...gu, ...Mu },
    Ht = _c(Du, Xc),
    Jt = [
        {
            headline: `Every game tells
a story.`,
            description:
                'Track your performance, challenge rivals, and climb the leaderboard. Your court, your legacy.',
        },
        {
            headline: `Rise through
the ranks.`,
            description:
                'Compete in challenges, earn badges, and prove you belong at the top. The leaderboard awaits.',
        },
        {
            headline: `Your highlights,
your legacy.`,
            description:
                'Upload game footage, review your plays, and share your best moments with the community.',
        },
        {
            headline: `Built for
competitors.`,
            description:
                'From pickup games to tournaments — track every stat, every win, every step of your journey.',
        },
        {
            headline: `The court
never lies.`,
            description:
                'Submit your scores, verify results, and let your game speak for itself. No shortcuts.',
        },
    ];
function Iu(t) {
    const e = Io.c(43),
        { children: n, title: s, description: i } = t,
        [r, o] = T.useState(0);
    let a, l;
    (e[0] === Symbol.for('react.memo_cache_sentinel')
        ? ((a = () => {
              const q = setInterval(() => {
                  o(Eu);
              }, 5e3);
              return () => clearInterval(q);
          }),
          (l = []),
          (e[0] = a),
          (e[1] = l))
        : ((a = e[0]), (l = e[1])),
        T.useEffect(a, l));
    let c, u;
    e[2] === Symbol.for('react.memo_cache_sentinel')
        ? ((c = C.jsx('img', {
              src: 'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=1400&q=80',
              alt: 'Youth playing basketball on an outdoor court',
              className: 'absolute inset-0 h-full w-full object-cover',
          })),
          (u = C.jsx('div', {
              className:
                  'absolute inset-0 bg-linear-to-r from-black/60 via-black/40 to-black/70',
          })),
          (e[2] = c),
          (e[3] = u))
        : ((c = e[2]), (u = e[3]));
    let h, f, d, m;
    e[4] === Symbol.for('react.memo_cache_sentinel')
        ? ((h = { opacity: 0, y: 16 }),
          (f = { opacity: 1, y: 0 }),
          (d = { opacity: 0, y: -12 }),
          (m = { duration: 0.4, ease: 'easeOut' }),
          (e[4] = h),
          (e[5] = f),
          (e[6] = d),
          (e[7] = m))
        : ((h = e[4]), (f = e[5]), (d = e[6]), (m = e[7]));
    const y = Jt[r];
    let p;
    e[8] !== y.headline
        ? ((p = C.jsx('h2', {
              className:
                  'mb-3 font-heading text-3xl leading-tight font-bold whitespace-pre-line text-white xl:text-4xl',
              children: y.headline,
          })),
          (e[8] = y.headline),
          (e[9] = p))
        : (p = e[9]);
    const g = Jt[r];
    let x;
    e[10] !== g.description
        ? ((x = C.jsx('p', {
              className:
                  'max-w-sm font-body text-sm leading-relaxed text-white/70',
              children: g.description,
          })),
          (e[10] = g.description),
          (e[11] = x))
        : (x = e[11]);
    let v;
    e[12] !== r || e[13] !== p || e[14] !== x
        ? ((v = C.jsx('div', {
              className: 'relative h-36 overflow-hidden',
              children: C.jsx(Pc, {
                  initial: !1,
                  mode: 'wait',
                  children: C.jsxs(
                      Ht.div,
                      {
                          className: 'absolute inset-0',
                          initial: h,
                          animate: f,
                          exit: d,
                          transition: m,
                          children: [p, x],
                      },
                      r,
                  ),
              }),
          })),
          (e[12] = r),
          (e[13] = p),
          (e[14] = x),
          (e[15] = v))
        : (v = e[15]);
    let S;
    e[16] !== r
        ? ((S = Jt.map((q, H) =>
              C.jsx(
                  'button',
                  {
                      type: 'button',
                      onClick: () => o(H),
                      className: `rounded-full transition-all duration-300 ${H === r ? 'h-1 w-6 bg-chart-1' : 'h-1.5 w-1.5 bg-white/30 hover:bg-white/50'}`,
                      'aria-label': `Go to slide ${H + 1}`,
                  },
                  H,
              ),
          )),
          (e[16] = r),
          (e[17] = S))
        : (S = e[17]);
    let w;
    e[18] !== S
        ? ((w = C.jsx('div', {
              className: 'mt-6 flex items-center gap-1.5',
              children: S,
          })),
          (e[18] = S),
          (e[19] = w))
        : (w = e[19]);
    let A;
    e[20] !== v || e[21] !== w
        ? ((A = C.jsxs('div', {
              className: 'relative hidden lg:block',
              children: [
                  c,
                  u,
                  C.jsxs('div', {
                      className:
                          'absolute inset-0 flex flex-col justify-end p-10 pb-12',
                      children: [v, w],
                  }),
              ],
          })),
          (e[20] = v),
          (e[21] = w),
          (e[22] = A))
        : (A = e[22]);
    let M, b, V;
    e[23] === Symbol.for('react.memo_cache_sentinel')
        ? ((M = { opacity: 0 }),
          (b = { opacity: 1 }),
          (V = { duration: 0.4, ease: 'easeOut' }),
          (e[23] = M),
          (e[24] = b),
          (e[25] = V))
        : ((M = e[23]), (b = e[24]), (V = e[25]));
    let E;
    e[26] === Symbol.for('react.memo_cache_sentinel')
        ? ((E = C.jsx(Ht.div, {
              className: 'mb-6 flex justify-center',
              initial: { opacity: 0, y: -12 },
              animate: { opacity: 1, y: 0 },
              transition: { duration: 0.4, delay: 0.3, ease: 'easeOut' },
              children: C.jsx(Fo, { className: 'h-9 w-auto' }),
          })),
          (e[26] = E))
        : (E = e[26]);
    let N, Y, st;
    e[27] === Symbol.for('react.memo_cache_sentinel')
        ? ((N = { opacity: 0, y: 16 }),
          (Y = { opacity: 1, y: 0 }),
          (st = { duration: 0.4, delay: 0.5, ease: 'easeOut' }),
          (e[27] = N),
          (e[28] = Y),
          (e[29] = st))
        : ((N = e[27]), (Y = e[28]), (st = e[29]));
    let tt;
    e[30] !== s
        ? ((tt = C.jsx('h1', {
              className: 'text-xl font-medium text-card-foreground',
              children: s,
          })),
          (e[30] = s),
          (e[31] = tt))
        : (tt = e[31]);
    let et;
    e[32] !== i
        ? ((et =
              i &&
              C.jsx('p', {
                  className: 'text-center text-sm text-muted-foreground',
                  children: i,
              })),
          (e[32] = i),
          (e[33] = et))
        : (et = e[33]);
    let lt;
    e[34] !== tt || e[35] !== et
        ? ((lt = C.jsx('div', {
              className: 'flex flex-col items-center gap-4',
              children: C.jsxs('div', {
                  className: 'space-y-2 text-center',
                  children: [tt, et],
              }),
          })),
          (e[34] = tt),
          (e[35] = et),
          (e[36] = lt))
        : (lt = e[36]);
    let R;
    e[37] !== n || e[38] !== lt
        ? ((R = C.jsx(Ht.div, {
              className:
                  'flex flex-col items-center justify-center bg-muted/30 px-8 py-10 sm:px-0 lg:px-10',
              initial: M,
              animate: b,
              transition: V,
              children: C.jsxs('div', {
                  className: 'w-full max-w-md',
                  children: [
                      E,
                      C.jsx(Ht.div, {
                          className:
                              'rounded-xl border border-border bg-card px-6 py-8 shadow-sm sm:px-8 sm:py-10',
                          initial: N,
                          animate: Y,
                          transition: st,
                          children: C.jsxs('div', {
                              className: 'flex flex-col gap-8',
                              children: [lt, n],
                          }),
                      }),
                  ],
              }),
          })),
          (e[37] = n),
          (e[38] = lt),
          (e[39] = R))
        : (R = e[39]);
    let j;
    return (
        e[40] !== A || e[41] !== R
            ? ((j = C.jsxs('div', {
                  className: 'grid min-h-dvh lg:grid-cols-2',
                  children: [A, R],
              })),
              (e[40] = A),
              (e[41] = R),
              (e[42] = j))
            : (j = e[42]),
        j
    );
}
function Eu(t) {
    return (t + 1) % Jt.length;
}
export { Iu as A };
