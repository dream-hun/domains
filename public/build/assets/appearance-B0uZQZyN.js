import { c as x, u as b, j as s, H as v } from './app-C_M1-u2r.js';
import { a as u, c as h } from './app-logo-icon-BcSq9Noa.js';
import { S as j, H as _, e as N } from './layout-87Jxz_JF.js';
import { A } from './app-layout-CZPTXx4s.js';
/* empty css            */ import './index-m3SvSfPp.js';
import './index-CSEUMRIG.js';
import './index-A1fKdpK_.js';
import './index-DAou6nP7.js';
import './index-x0I2YQpJ.js';
import './input-Cn0z7F4k.js';
const S = [
        [
            'rect',
            {
                width: '20',
                height: '14',
                x: '2',
                y: '3',
                rx: '2',
                key: '48i651',
            },
        ],
        ['line', { x1: '8', x2: '16', y1: '21', y2: '21', key: '1svkeh' }],
        ['line', { x1: '12', x2: '12', y1: '17', y2: '21', key: 'vw1qmm' }],
    ],
    w = u('monitor', S);
const M = [
        [
            'path',
            {
                d: 'M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401',
                key: 'kfwtm',
            },
        ],
    ],
    H = u('moon', M);
const L = [
        ['circle', { cx: '12', cy: '12', r: '4', key: '4exip2' }],
        ['path', { d: 'M12 2v2', key: 'tus03m' }],
        ['path', { d: 'M12 20v2', key: '1lh1kg' }],
        ['path', { d: 'm4.93 4.93 1.41 1.41', key: '149t6j' }],
        ['path', { d: 'm17.66 17.66 1.41 1.41', key: 'ptbguv' }],
        ['path', { d: 'M2 12h2', key: '1t8f8n' }],
        ['path', { d: 'M20 12h2', key: '1q8mjw' }],
        ['path', { d: 'm6.34 17.66-1.41 1.41', key: '1m8zz5' }],
        ['path', { d: 'm19.07 4.93-1.41 1.41', key: '1shlcs' }],
    ],
    $ = u('sun', L);
function q(t) {
    const e = x.c(13);
    let a, n;
    e[0] !== t
        ? (({ className: n, ...a } = t), (e[0] = t), (e[1] = a), (e[2] = n))
        : ((a = e[1]), (n = e[2]));
    const i = n === void 0 ? '' : n,
        { appearance: p, updateAppearance: m } = b();
    let l;
    e[3] === Symbol.for('react.memo_cache_sentinel')
        ? ((l = [
              { value: 'light', icon: $, label: 'Light' },
              { value: 'dark', icon: H, label: 'Dark' },
              { value: 'system', icon: w, label: 'System' },
          ]),
          (e[3] = l))
        : (l = e[3]);
    const y = l;
    let r;
    e[4] !== i
        ? ((r = h(
              'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
              i,
          )),
          (e[4] = i),
          (e[5] = r))
        : (r = e[5]);
    let c;
    e[6] !== p || e[7] !== m
        ? ((c = y.map((k) => {
              const { value: d, icon: f, label: g } = k;
              return s.jsxs(
                  'button',
                  {
                      onClick: () => m(d),
                      className: h(
                          'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                          p === d
                              ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                              : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                      ),
                      children: [
                          s.jsx(f, { className: '-ml-1 h-4 w-4' }),
                          s.jsx('span', {
                              className: 'ml-1.5 text-sm',
                              children: g,
                          }),
                      ],
                  },
                  d,
              );
          })),
          (e[6] = p),
          (e[7] = m),
          (e[8] = c))
        : (c = e[8]);
    let o;
    return (
        e[9] !== a || e[10] !== r || e[11] !== c
            ? ((o = s.jsx('div', { className: r, ...a, children: c })),
              (e[9] = a),
              (e[10] = r),
              (e[11] = c),
              (e[12] = o))
            : (o = e[12]),
        o
    );
}
const z = [{ title: 'Appearance settings', href: N().url }];
function K() {
    const t = x.c(3);
    let e, a;
    t[0] === Symbol.for('react.memo_cache_sentinel')
        ? ((e = s.jsx(v, { title: 'Appearance settings' })),
          (a = s.jsx('h1', {
              className: 'sr-only',
              children: 'Appearance Settings',
          })),
          (t[0] = e),
          (t[1] = a))
        : ((e = t[0]), (a = t[1]));
    let n;
    return (
        t[2] === Symbol.for('react.memo_cache_sentinel')
            ? ((n = s.jsxs(A, {
                  breadcrumbs: z,
                  children: [
                      e,
                      a,
                      s.jsx(j, {
                          children: s.jsxs('div', {
                              className: 'space-y-6',
                              children: [
                                  s.jsx(_, {
                                      variant: 'small',
                                      title: 'Appearance settings',
                                      description:
                                          "Update your account's appearance settings",
                                  }),
                                  s.jsx(q, {}),
                              ],
                          }),
                      }),
                  ],
              })),
              (t[2] = n))
            : (n = t[2]),
        n
    );
}
export { K as default };
