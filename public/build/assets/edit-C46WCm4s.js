import { c as j, j as e, F as y, H as q } from './app-C_M1-u2r.js';
import { q as _ } from './index-CSEUMRIG.js';
import { I as c } from './input-error-CkKI5kvP.js';
import { B as b } from './app-logo-icon-BcSq9Noa.js';
import { C, a as F, b as N, c as v, d as k } from './card-DkGukznh.js';
import { I as h } from './input-Cn0z7F4k.js';
import { L as g } from './label-CKqDLf1o.js';
import { A as R } from './app-layout-CZPTXx4s.js';
/* empty css            */ import './index-m3SvSfPp.js';
import './index-DAou6nP7.js';
import './index-x0I2YQpJ.js';
const r = (t) => ({ url: r.url(t), method: 'get' });
r.definition = { methods: ['get', 'head'], url: '/admin/ranking' };
r.url = (t) => r.definition.url + _(t);
r.get = (t) => ({ url: r.url(t), method: 'get' });
r.head = (t) => ({ url: r.url(t), method: 'head' });
const f = (t) => ({ action: r.url(t), method: 'get' });
f.get = (t) => ({ action: r.url(t), method: 'get' });
f.head = (t) => ({
    action: r.url({
        [t?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(t?.query ?? t?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
r.form = f;
const a = (t) => ({ url: a.url(t), method: 'post' });
a.definition = { methods: ['post'], url: '/admin/ranking' };
a.url = (t) => a.definition.url + _(t);
a.post = (t) => ({ url: a.url(t), method: 'post' });
const x = (t) => ({ action: a.url(t), method: 'post' });
x.post = (t) => ({ action: a.url(t), method: 'post' });
a.form = x;
const S = [{ title: 'Ranking Configuration', href: r().url }];
function T(t) {
    const i = j.c(9),
        { config: s } = t;
    let n;
    i[0] === Symbol.for('react.memo_cache_sentinel')
        ? ((n = e.jsx(q, { title: 'Ranking Configuration' })), (i[0] = n))
        : (n = i[0]);
    let o;
    i[1] === Symbol.for('react.memo_cache_sentinel')
        ? ((o = e.jsxs('div', {
              children: [
                  e.jsx('h1', {
                      className: 'text-2xl font-semibold',
                      children: 'Ranking Configuration',
                  }),
                  e.jsx('p', {
                      className: 'text-sm text-muted-foreground',
                      children:
                          'Adjust the weights used to calculate player scores. A new configuration is saved on each update and recalculation is queued automatically.',
                  }),
              ],
          })),
          (i[1] = o))
        : (o = i[1]);
    let l;
    i[2] === Symbol.for('react.memo_cache_sentinel')
        ? ((l = e.jsxs(N, {
              children: [
                  e.jsx(v, { children: 'Score Formula' }),
                  e.jsx(k, {
                      children:
                          'score = (wins × win_weight) + (losses × loss_weight) + (total_games × game_count_weight) + (recent_30d_games × frequency_weight)',
                  }),
              ],
          })),
          (i[2] = l))
        : (l = i[2]);
    let m;
    i[3] === Symbol.for('react.memo_cache_sentinel')
        ? ((m = a.form()), (i[3] = m))
        : (m = i[3]);
    let u;
    return (
        i[4] !== s.frequency_weight ||
        i[5] !== s.game_count_weight ||
        i[6] !== s.loss_weight ||
        i[7] !== s.win_weight
            ? ((u = e.jsxs(R, {
                  breadcrumbs: S,
                  children: [
                      n,
                      e.jsxs('div', {
                          className: 'flex flex-col gap-6 p-6 max-w-2xl',
                          children: [
                              o,
                              e.jsxs(C, {
                                  children: [
                                      l,
                                      e.jsx(F, {
                                          children: e.jsx(y, {
                                              ...m,
                                              className: 'flex flex-col gap-4',
                                              children: (p) => {
                                                  const {
                                                      processing: w,
                                                      errors: d,
                                                  } = p;
                                                  return e.jsxs(e.Fragment, {
                                                      children: [
                                                          e.jsxs('div', {
                                                              className:
                                                                  'grid gap-2',
                                                              children: [
                                                                  e.jsx(g, {
                                                                      htmlFor:
                                                                          'win_weight',
                                                                      children:
                                                                          'Win Weight',
                                                                  }),
                                                                  e.jsx(h, {
                                                                      id: 'win_weight',
                                                                      name: 'win_weight',
                                                                      type: 'number',
                                                                      step: '0.01',
                                                                      min: '0',
                                                                      max: '100',
                                                                      defaultValue:
                                                                          s.win_weight,
                                                                      required:
                                                                          !0,
                                                                  }),
                                                                  e.jsx(c, {
                                                                      message:
                                                                          d.win_weight,
                                                                  }),
                                                              ],
                                                          }),
                                                          e.jsxs('div', {
                                                              className:
                                                                  'grid gap-2',
                                                              children: [
                                                                  e.jsx(g, {
                                                                      htmlFor:
                                                                          'loss_weight',
                                                                      children:
                                                                          'Loss Weight',
                                                                  }),
                                                                  e.jsx(h, {
                                                                      id: 'loss_weight',
                                                                      name: 'loss_weight',
                                                                      type: 'number',
                                                                      step: '0.01',
                                                                      min: '0',
                                                                      max: '100',
                                                                      defaultValue:
                                                                          s.loss_weight,
                                                                      required:
                                                                          !0,
                                                                  }),
                                                                  e.jsx(c, {
                                                                      message:
                                                                          d.loss_weight,
                                                                  }),
                                                              ],
                                                          }),
                                                          e.jsxs('div', {
                                                              className:
                                                                  'grid gap-2',
                                                              children: [
                                                                  e.jsx(g, {
                                                                      htmlFor:
                                                                          'game_count_weight',
                                                                      children:
                                                                          'Game Count Weight',
                                                                  }),
                                                                  e.jsx(h, {
                                                                      id: 'game_count_weight',
                                                                      name: 'game_count_weight',
                                                                      type: 'number',
                                                                      step: '0.01',
                                                                      min: '0',
                                                                      max: '100',
                                                                      defaultValue:
                                                                          s.game_count_weight,
                                                                      required:
                                                                          !0,
                                                                  }),
                                                                  e.jsx(c, {
                                                                      message:
                                                                          d.game_count_weight,
                                                                  }),
                                                              ],
                                                          }),
                                                          e.jsxs('div', {
                                                              className:
                                                                  'grid gap-2',
                                                              children: [
                                                                  e.jsx(g, {
                                                                      htmlFor:
                                                                          'frequency_weight',
                                                                      children:
                                                                          'Frequency Weight (last 30 days)',
                                                                  }),
                                                                  e.jsx(h, {
                                                                      id: 'frequency_weight',
                                                                      name: 'frequency_weight',
                                                                      type: 'number',
                                                                      step: '0.01',
                                                                      min: '0',
                                                                      max: '100',
                                                                      defaultValue:
                                                                          s.frequency_weight,
                                                                      required:
                                                                          !0,
                                                                  }),
                                                                  e.jsx(c, {
                                                                      message:
                                                                          d.frequency_weight,
                                                                  }),
                                                              ],
                                                          }),
                                                          e.jsx(b, {
                                                              disabled: w,
                                                              asChild: !0,
                                                              className:
                                                                  'w-fit',
                                                              children: e.jsx(
                                                                  'button',
                                                                  {
                                                                      type: 'submit',
                                                                      children:
                                                                          'Save Configuration',
                                                                  },
                                                              ),
                                                          }),
                                                      ],
                                                  });
                                              },
                                          }),
                                      }),
                                  ],
                              }),
                          ],
                      }),
                  ],
              })),
              (i[4] = s.frequency_weight),
              (i[5] = s.game_count_weight),
              (i[6] = s.loss_weight),
              (i[7] = s.win_weight),
              (i[8] = u))
            : (u = i[8]),
        u
    );
}
export { T as default };
