import {
    c as Pe,
    r as K,
    j as t,
    L as Me,
    F as ge,
    a as Ve,
    H as Oe,
} from './app-C_M1-u2r.js';
import {
    D as $e,
    a as ke,
    b as He,
    c as _e,
    G as xe,
    d as ze,
    e as Ae,
    S as Be,
    A as Re,
} from './app-layout-CZPTXx4s.js';
import { I as ee } from './input-error-CkKI5kvP.js';
import { a as Ue, B as k, c as qe } from './app-logo-icon-BcSq9Noa.js';
import {
    C as We,
    a as Ye,
    b as Je,
    c as Ke,
    P as Qe,
} from './popover-J-jCX8RG.js';
import {
    D as ye,
    a as Te,
    b as Ee,
    c as be,
    d as Se,
    e as ve,
    f as Ne,
} from './dialog-CcgQnQM2.js';
import { I as fe } from './input-Cn0z7F4k.js';
import { L as re } from './label-CKqDLf1o.js';
import {
    d as pe,
    a as Ce,
    b as De,
    c as we,
    S as Fe,
} from './select-BLt20f7s.js';
import {
    T as Ge,
    a as $,
    b as Xe,
    c as J,
    d as Ze,
    e as et,
} from './table-BdOh_5ux.js';
import { E as tt } from './ellipsis-TiXYScHK.js';
/* empty css            */ import './index-m3SvSfPp.js';
import './index-DAou6nP7.js';
import './index-CSEUMRIG.js';
import './index-x0I2YQpJ.js';
import './index-2rmVfgAx.js';
import './check-zt7p40bQ.js';
const st = [
        ['circle', { cx: '12', cy: '12', r: '10', key: '1mglay' }],
        ['path', { d: 'M8 12h8', key: '1wcyev' }],
        ['path', { d: 'M12 8v8', key: 'napkw2' }],
    ],
    lt = Ue('circle-plus', st),
    rt = ['1v1', '2v2', '3v3', '4v4', '5v5'],
    at = [{ title: 'Games', href: Ae().url }];
function nt(l) {
    const e = {
        pending: 'bg-yellow-500',
        approved: 'bg-green-500',
        rejected: 'bg-red-500',
        flagged: 'bg-orange-500',
    };
    return t.jsx('span', {
        className: `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${e[l] ?? 'bg-gray-400'}`,
        children: l,
    });
}
function it(l) {
    if (!l)
        return t.jsx('span', {
            className: 'text-xs text-muted-foreground',
            children: 'No video',
        });
    const e = { pending: 'bg-yellow-500', complete: 'bg-green-500' };
    return t.jsx('span', {
        className: `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${e[l] ?? 'bg-gray-400'}`,
        children: l,
    });
}
function Le(l) {
    const e = Pe.c(94),
        { game: a, courts: me, errors: r } = l;
    let H;
    e[0] !== a
        ? ((H = a?.played_at ? new Date(a.played_at) : void 0),
          (e[0] = a),
          (e[1] = H))
        : (H = e[1]);
    const [n, z] = K.useState(H),
        [te, B] = K.useState(!1);
    let Q;
    e[2] !== n
        ? ((Q = n
              ? `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`
              : ''),
          (e[2] = n),
          (e[3] = Q))
        : (Q = e[3]);
    const j = Q;
    let ae;
    e[4] === Symbol.for('react.memo_cache_sentinel')
        ? ((ae = t.jsx(re, { htmlFor: 'title', children: 'Title' })),
          (e[4] = ae))
        : (ae = e[4]);
    const X = a?.title;
    let g;
    e[5] !== X
        ? ((g = t.jsx(fe, {
              id: 'title',
              name: 'title',
              defaultValue: X,
              placeholder: 'Game title',
              required: !0,
          })),
          (e[5] = X),
          (e[6] = g))
        : (g = e[6]);
    let _;
    e[7] !== r.title
        ? ((_ = t.jsx(ee, { message: r.title })), (e[7] = r.title), (e[8] = _))
        : (_ = e[8]);
    let y;
    e[9] !== g || e[10] !== _
        ? ((y = t.jsxs('div', {
              className: 'grid gap-2',
              children: [ae, g, _],
          })),
          (e[9] = g),
          (e[10] = _),
          (e[11] = y))
        : (y = e[11]);
    let b;
    e[12] === Symbol.for('react.memo_cache_sentinel')
        ? ((b = t.jsx(re, { htmlFor: 'format', children: 'Format' })),
          (e[12] = b))
        : (b = e[12]);
    const Z = a?.format ?? '5v5';
    let R;
    e[13] === Symbol.for('react.memo_cache_sentinel')
        ? ((R = t.jsx(Ce, {
              id: 'format',
              children: t.jsx(De, { placeholder: 'Select format' }),
          })),
          (e[13] = R))
        : (R = e[13]);
    let S;
    e[14] === Symbol.for('react.memo_cache_sentinel')
        ? ((S = t.jsx(we, { children: rt.map(ot) })), (e[14] = S))
        : (S = e[14]);
    let v;
    e[15] !== Z
        ? ((v = t.jsxs(Fe, {
              name: 'format',
              defaultValue: Z,
              required: !0,
              children: [R, S],
          })),
          (e[15] = Z),
          (e[16] = v))
        : (v = e[16]);
    let i;
    e[17] !== r.format
        ? ((i = t.jsx(ee, { message: r.format })),
          (e[17] = r.format),
          (e[18] = i))
        : (i = e[18]);
    let c;
    e[19] !== v || e[20] !== i
        ? ((c = t.jsxs('div', {
              className: 'grid gap-2',
              children: [b, v, i],
          })),
          (e[19] = v),
          (e[20] = i),
          (e[21] = c))
        : (c = e[21]);
    let U;
    e[22] === Symbol.for('react.memo_cache_sentinel')
        ? ((U = t.jsx(re, { htmlFor: 'court_id', children: 'Court' })),
          (e[22] = U))
        : (U = e[22]);
    const P = a?.court_id ? String(a.court_id) : '';
    let N;
    e[23] === Symbol.for('react.memo_cache_sentinel')
        ? ((N = t.jsx(Ce, {
              id: 'court_id',
              children: t.jsx(De, { placeholder: 'Select a court (optional)' }),
          })),
          (e[23] = N))
        : (N = e[23]);
    let o;
    e[24] !== me ? ((o = me.map(ct)), (e[24] = me), (e[25] = o)) : (o = e[25]);
    let d;
    e[26] !== o
        ? ((d = t.jsx(we, { children: o })), (e[26] = o), (e[27] = d))
        : (d = e[27]);
    let C;
    e[28] !== P || e[29] !== d
        ? ((C = t.jsxs(Fe, {
              name: 'court_id',
              defaultValue: P,
              children: [N, d],
          })),
          (e[28] = P),
          (e[29] = d),
          (e[30] = C))
        : (C = e[30]);
    let D;
    e[31] !== r.court_id
        ? ((D = t.jsx(ee, { message: r.court_id })),
          (e[31] = r.court_id),
          (e[32] = D))
        : (D = e[32]);
    let M;
    e[33] !== C || e[34] !== D
        ? ((M = t.jsxs('div', {
              className: 'grid gap-2',
              children: [U, C, D],
          })),
          (e[33] = C),
          (e[34] = D),
          (e[35] = M))
        : (M = e[35]);
    let q;
    e[36] === Symbol.for('react.memo_cache_sentinel')
        ? ((q = t.jsx(re, { children: 'Played At' })), (e[36] = q))
        : (q = e[36]);
    const A = !n && 'text-muted-foreground';
    let m;
    e[37] !== A
        ? ((m = qe('justify-start font-normal', A)), (e[37] = A), (e[38] = m))
        : (m = e[38]);
    let w;
    e[39] === Symbol.for('react.memo_cache_sentinel')
        ? ((w = t.jsx(We, { className: 'mr-2 size-4' })), (e[39] = w))
        : (w = e[39]);
    let I;
    e[40] !== n
        ? ((I = n
              ? n.toLocaleDateString('default', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                })
              : 'Pick a date'),
          (e[40] = n),
          (e[41] = I))
        : (I = e[41]);
    let F;
    e[42] !== m || e[43] !== I
        ? ((F = t.jsx(Ye, {
              asChild: !0,
              children: t.jsxs(k, {
                  variant: 'outline',
                  type: 'button',
                  className: m,
                  children: [w, I],
              }),
          })),
          (e[42] = m),
          (e[43] = I),
          (e[44] = F))
        : (F = e[44]);
    let W;
    e[45] === Symbol.for('react.memo_cache_sentinel')
        ? ((W = (Ie) => {
              (z(Ie), B(!1));
          }),
          (e[45] = W))
        : (W = e[45]);
    let h;
    e[46] !== n
        ? ((h = t.jsx(Je, {
              align: 'start',
              children: t.jsx(Ke, { mode: 'single', selected: n, onSelect: W }),
          })),
          (e[46] = n),
          (e[47] = h))
        : (h = e[47]);
    let u;
    e[48] !== te || e[49] !== F || e[50] !== h
        ? ((u = t.jsxs(Qe, { open: te, onOpenChange: B, children: [F, h] })),
          (e[48] = te),
          (e[49] = F),
          (e[50] = h),
          (e[51] = u))
        : (u = e[51]);
    let x;
    e[52] !== j
        ? ((x = t.jsx('input', {
              type: 'hidden',
              name: 'played_at',
              value: j,
          })),
          (e[52] = j),
          (e[53] = x))
        : (x = e[53]);
    let V;
    e[54] !== r.played_at
        ? ((V = t.jsx(ee, { message: r.played_at })),
          (e[54] = r.played_at),
          (e[55] = V))
        : (V = e[55]);
    let G;
    e[56] !== u || e[57] !== x || e[58] !== V
        ? ((G = t.jsxs('div', {
              className: 'grid gap-2',
              children: [q, u, x, V],
          })),
          (e[56] = u),
          (e[57] = x),
          (e[58] = V),
          (e[59] = G))
        : (G = e[59]);
    let Y;
    e[60] === Symbol.for('react.memo_cache_sentinel')
        ? ((Y = t.jsx(re, { htmlFor: 'result', children: 'Result' })),
          (e[60] = Y))
        : (Y = e[60]);
    const se = a?.result ?? '';
    let T;
    e[61] === Symbol.for('react.memo_cache_sentinel')
        ? ((T = t.jsx(Ce, {
              id: 'result',
              children: t.jsx(De, { placeholder: 'Select result (optional)' }),
          })),
          (e[61] = T))
        : (T = e[61]);
    let E;
    e[62] === Symbol.for('react.memo_cache_sentinel')
        ? ((E = t.jsxs(we, {
              children: [
                  t.jsx(pe, { value: 'win', children: 'Win' }),
                  t.jsx(pe, { value: 'lost', children: 'Lost' }),
              ],
          })),
          (e[62] = E))
        : (E = e[62]);
    let f;
    e[63] !== se
        ? ((f = t.jsxs(Fe, {
              name: 'result',
              defaultValue: se,
              children: [T, E],
          })),
          (e[63] = se),
          (e[64] = f))
        : (f = e[64]);
    let p;
    e[65] !== r.result
        ? ((p = t.jsx(ee, { message: r.result })),
          (e[65] = r.result),
          (e[66] = p))
        : (p = e[66]);
    let L;
    e[67] !== f || e[68] !== p
        ? ((L = t.jsxs('div', {
              className: 'grid gap-2',
              children: [Y, f, p],
          })),
          (e[67] = f),
          (e[68] = p),
          (e[69] = L))
        : (L = e[69]);
    let s;
    e[70] === Symbol.for('react.memo_cache_sentinel')
        ? ((s = t.jsx(re, { htmlFor: 'points', children: 'Points' })),
          (e[70] = s))
        : (s = e[70]);
    const le = a?.points ?? '';
    let O;
    e[71] !== le
        ? ((O = t.jsx(fe, {
              id: 'points',
              name: 'points',
              type: 'number',
              min: 0,
              defaultValue: le,
              placeholder: 'Points scored (optional)',
          })),
          (e[71] = le),
          (e[72] = O))
        : (O = e[72]);
    let ne;
    e[73] !== r.points
        ? ((ne = t.jsx(ee, { message: r.points })),
          (e[73] = r.points),
          (e[74] = ne))
        : (ne = e[74]);
    let ie;
    e[75] !== O || e[76] !== ne
        ? ((ie = t.jsxs('div', {
              className: 'grid gap-2',
              children: [s, O, ne],
          })),
          (e[75] = O),
          (e[76] = ne),
          (e[77] = ie))
        : (ie = e[77]);
    let he;
    e[78] === Symbol.for('react.memo_cache_sentinel')
        ? ((he = t.jsx(re, { htmlFor: 'comments', children: 'Comments' })),
          (e[78] = he))
        : (he = e[78]);
    const je = a?.comments ?? '';
    let ce;
    e[79] !== je
        ? ((ce = t.jsx(fe, {
              id: 'comments',
              name: 'comments',
              defaultValue: je,
              placeholder: 'Comments (optional)',
          })),
          (e[79] = je),
          (e[80] = ce))
        : (ce = e[80]);
    let oe;
    e[81] !== r.comments
        ? ((oe = t.jsx(ee, { message: r.comments })),
          (e[81] = r.comments),
          (e[82] = oe))
        : (oe = e[82]);
    let de;
    e[83] !== ce || e[84] !== oe
        ? ((de = t.jsxs('div', {
              className: 'grid gap-2',
              children: [he, ce, oe],
          })),
          (e[83] = ce),
          (e[84] = oe),
          (e[85] = de))
        : (de = e[85]);
    let ue;
    return (
        e[86] !== c ||
        e[87] !== M ||
        e[88] !== G ||
        e[89] !== L ||
        e[90] !== ie ||
        e[91] !== de ||
        e[92] !== y
            ? ((ue = t.jsxs(t.Fragment, { children: [y, c, M, G, L, ie, de] })),
              (e[86] = c),
              (e[87] = M),
              (e[88] = G),
              (e[89] = L),
              (e[90] = ie),
              (e[91] = de),
              (e[92] = y),
              (e[93] = ue))
            : (ue = e[93]),
        ue
    );
}
function ct(l) {
    return t.jsx(pe, { value: String(l.id), children: l.name }, l.id);
}
function ot(l) {
    return t.jsx(pe, { value: l, children: l }, l);
}
function Tt(l) {
    const e = Pe.c(67),
        { games: a, filters: me, courts: r } = l,
        [H, n] = K.useState(!1),
        [z, te] = K.useState(null),
        [B, Q] = K.useState(null),
        [j, ae] = K.useState(me.search ?? '');
    let X, g;
    (e[0] !== j
        ? ((X = () => {
              const s = setTimeout(() => {
                  Ve.get(
                      Ae().url,
                      { search: j || void 0 },
                      { preserveState: !0, replace: !0 },
                  );
              }, 300);
              return () => clearTimeout(s);
          }),
          (g = [j]),
          (e[0] = j),
          (e[1] = X),
          (e[2] = g))
        : ((X = e[1]), (g = e[2])),
        K.useEffect(X, g));
    let _;
    e[3] === Symbol.for('react.memo_cache_sentinel')
        ? ((_ = t.jsx(Oe, { title: 'Games' })), (e[3] = _))
        : (_ = e[3]);
    let y;
    e[4] === Symbol.for('react.memo_cache_sentinel')
        ? ((y = t.jsx('h1', {
              className: 'text-2xl font-semibold',
              children: 'Games',
          })),
          (e[4] = y))
        : (y = e[4]);
    let b;
    e[5] !== a.total
        ? ((b = t.jsxs('div', {
              children: [
                  y,
                  t.jsxs('p', {
                      className: 'text-sm text-muted-foreground',
                      children: ['Manage all games (', a.total, ' total)'],
                  }),
              ],
          })),
          (e[5] = a.total),
          (e[6] = b))
        : (b = e[6]);
    let Z;
    e[7] === Symbol.for('react.memo_cache_sentinel')
        ? ((Z = t.jsx(Be, {
              className:
                  'absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground',
          })),
          (e[7] = Z))
        : (Z = e[7]);
    let R;
    e[8] === Symbol.for('react.memo_cache_sentinel')
        ? ((R = (s) => ae(s.target.value)), (e[8] = R))
        : (R = e[8]);
    let S;
    e[9] !== j
        ? ((S = t.jsxs('div', {
              className: 'relative w-64',
              children: [
                  Z,
                  t.jsx(fe, {
                      placeholder: 'Search games...',
                      value: j,
                      onChange: R,
                      className: 'pl-8',
                  }),
              ],
          })),
          (e[9] = j),
          (e[10] = S))
        : (S = e[10]);
    let v;
    e[11] === Symbol.for('react.memo_cache_sentinel')
        ? ((v = t.jsxs(k, {
              onClick: () => n(!0),
              children: [t.jsx(lt, {}), 'Add Game'],
          })),
          (e[11] = v))
        : (v = e[11]);
    let i;
    e[12] !== S
        ? ((i = t.jsxs('div', {
              className: 'flex items-center gap-3',
              children: [S, v],
          })),
          (e[12] = S),
          (e[13] = i))
        : (i = e[13]);
    let c;
    e[14] !== i || e[15] !== b
        ? ((c = t.jsxs('div', {
              className: 'flex items-center justify-between',
              children: [b, i],
          })),
          (e[14] = i),
          (e[15] = b),
          (e[16] = c))
        : (c = e[16]);
    let U;
    e[17] === Symbol.for('react.memo_cache_sentinel')
        ? ((U = t.jsx(Xe, {
              children: t.jsxs(Ge, {
                  children: [
                      t.jsx(J, { children: 'Title' }),
                      t.jsx(J, { children: 'Player' }),
                      t.jsx(J, { children: 'Format' }),
                      t.jsx(J, { children: 'Played At' }),
                      t.jsx(J, { children: 'Status' }),
                      t.jsx(J, { children: 'Result' }),
                      t.jsx(J, { children: 'Points' }),
                      t.jsx(J, { children: 'Video' }),
                      t.jsx(J, {
                          className: 'text-right',
                          children: 'Actions',
                      }),
                  ],
              }),
          })),
          (e[17] = U))
        : (U = e[17]);
    let P;
    e[18] !== a.data
        ? ((P =
              a.data.length === 0
                  ? t.jsx(Ge, {
                        children: t.jsx($, {
                            colSpan: 9,
                            className: 'py-8 text-center text-muted-foreground',
                            children: 'No games found.',
                        }),
                    })
                  : a.data.map((s) =>
                        t.jsxs(
                            Ge,
                            {
                                children: [
                                    t.jsx($, {
                                        className: 'font-medium',
                                        children: s.title,
                                    }),
                                    t.jsx($, {
                                        children: s.player?.name ?? '—',
                                    }),
                                    t.jsx($, { children: s.format }),
                                    t.jsx($, {
                                        children: new Date(
                                            s.played_at,
                                        ).toLocaleDateString(),
                                    }),
                                    t.jsx($, { children: nt(s.status) }),
                                    t.jsx($, {
                                        children: s.result
                                            ? t.jsx('span', {
                                                  className: `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${s.result === 'win' ? 'bg-green-500' : 'bg-red-500'}`,
                                                  children:
                                                      s.result === 'win'
                                                          ? 'Win'
                                                          : 'Lost',
                                              })
                                            : t.jsx('span', {
                                                  className:
                                                      'text-xs text-muted-foreground',
                                                  children: '—',
                                              }),
                                    }),
                                    t.jsx($, {
                                        children:
                                            s.points ??
                                            t.jsx('span', {
                                                className:
                                                    'text-xs text-muted-foreground',
                                                children: '—',
                                            }),
                                    }),
                                    t.jsx($, { children: it(s.vimeo_status) }),
                                    t.jsx($, {
                                        className: 'text-right',
                                        children: t.jsxs($e, {
                                            children: [
                                                t.jsx(ke, {
                                                    asChild: !0,
                                                    children: t.jsxs(k, {
                                                        variant: 'ghost',
                                                        size: 'icon',
                                                        children: [
                                                            t.jsx(tt, {
                                                                className:
                                                                    'size-4',
                                                            }),
                                                            t.jsx('span', {
                                                                className:
                                                                    'sr-only',
                                                                children:
                                                                    'Actions',
                                                            }),
                                                        ],
                                                    }),
                                                }),
                                                t.jsxs(He, {
                                                    align: 'end',
                                                    children: [
                                                        t.jsx(_e, {
                                                            onClick: () =>
                                                                te(s),
                                                            children: 'Edit',
                                                        }),
                                                        t.jsx(_e, {
                                                            asChild: !0,
                                                            children: t.jsx(
                                                                Me,
                                                                {
                                                                    href: xe.showUpload(
                                                                        s.uuid,
                                                                    ).url,
                                                                    children:
                                                                        'Upload Video',
                                                                },
                                                            ),
                                                        }),
                                                        t.jsx(ze, {}),
                                                        t.jsx(_e, {
                                                            variant:
                                                                'destructive',
                                                            onClick: () => Q(s),
                                                            children: 'Delete',
                                                        }),
                                                    ],
                                                }),
                                            ],
                                        }),
                                    }),
                                ],
                            },
                            s.id,
                        ),
                    )),
          (e[18] = a.data),
          (e[19] = P))
        : (P = e[19]);
    let N;
    e[20] !== P
        ? ((N = t.jsx('div', {
              className: 'rounded-md border',
              children: t.jsxs(Ze, {
                  children: [U, t.jsx(et, { children: P })],
              }),
          })),
          (e[20] = P),
          (e[21] = N))
        : (N = e[21]);
    let o;
    e[22] !== a.last_page || e[23] !== a.links
        ? ((o =
              a.last_page > 1 &&
              t.jsx('div', {
                  className: 'flex items-center justify-center gap-1',
                  children: a.links.map(mt),
              })),
          (e[22] = a.last_page),
          (e[23] = a.links),
          (e[24] = o))
        : (o = e[24]);
    let d;
    e[25] !== c || e[26] !== N || e[27] !== o
        ? ((d = t.jsxs('div', {
              className: 'flex flex-col gap-6 p-6',
              children: [c, N, o],
          })),
          (e[25] = c),
          (e[26] = N),
          (e[27] = o),
          (e[28] = d))
        : (d = e[28]);
    let C;
    e[29] === Symbol.for('react.memo_cache_sentinel')
        ? ((C = t.jsxs(be, {
              children: [
                  t.jsx(Se, { children: 'Create Game' }),
                  t.jsx(ve, { children: 'Add a new game record.' }),
              ],
          })),
          (e[29] = C))
        : (C = e[29]);
    let D;
    e[30] === Symbol.for('react.memo_cache_sentinel')
        ? ((D = xe.store.form()), (e[30] = D))
        : (D = e[30]);
    const M = H ? 'open' : 'closed';
    let q;
    e[31] === Symbol.for('react.memo_cache_sentinel')
        ? ((q = () => n(!1)), (e[31] = q))
        : (q = e[31]);
    let A;
    e[32] !== r
        ? ((A = (s) => {
              const { processing: le, errors: O } = s;
              return t.jsxs(t.Fragment, {
                  children: [
                      t.jsx(Le, { courts: r, errors: O }),
                      t.jsxs(Te, {
                          className: 'gap-2',
                          children: [
                              t.jsx(Ee, {
                                  asChild: !0,
                                  children: t.jsx(k, {
                                      variant: 'secondary',
                                      children: 'Cancel',
                                  }),
                              }),
                              t.jsx(k, {
                                  disabled: le,
                                  asChild: !0,
                                  children: t.jsx('button', {
                                      type: 'submit',
                                      children: 'Create Game',
                                  }),
                              }),
                          ],
                      }),
                  ],
              });
          }),
          (e[32] = r),
          (e[33] = A))
        : (A = e[33]);
    let m;
    e[34] !== M || e[35] !== A
        ? ((m = t.jsxs(ye, {
              children: [
                  C,
                  K.createElement(
                      ge,
                      {
                          ...D,
                          key: M,
                          resetOnSuccess: !0,
                          onSuccess: q,
                          className: 'space-y-4',
                      },
                      A,
                  ),
              ],
          })),
          (e[34] = M),
          (e[35] = A),
          (e[36] = m))
        : (m = e[36]);
    let w;
    e[37] !== H || e[38] !== m
        ? ((w = t.jsx(Ne, { open: H, onOpenChange: n, children: m })),
          (e[37] = H),
          (e[38] = m),
          (e[39] = w))
        : (w = e[39]);
    const I = z !== null;
    let F;
    e[40] === Symbol.for('react.memo_cache_sentinel')
        ? ((F = (s) => {
              s || te(null);
          }),
          (e[40] = F))
        : (F = e[40]);
    let W;
    e[41] === Symbol.for('react.memo_cache_sentinel')
        ? ((W = t.jsxs(be, {
              children: [
                  t.jsx(Se, { children: 'Edit Game' }),
                  t.jsx(ve, { children: 'Update game details.' }),
              ],
          })),
          (e[41] = W))
        : (W = e[41]);
    let h;
    e[42] !== r || e[43] !== z
        ? ((h =
              z &&
              K.createElement(
                  ge,
                  {
                      ...xe.update.form(z.uuid),
                      key: z.id,
                      onSuccess: () => te(null),
                      className: 'space-y-4',
                  },
                  (s) => {
                      const { processing: le, errors: O } = s;
                      return t.jsxs(t.Fragment, {
                          children: [
                              t.jsx(Le, { game: z, courts: r, errors: O }),
                              t.jsxs(Te, {
                                  className: 'gap-2',
                                  children: [
                                      t.jsx(Ee, {
                                          asChild: !0,
                                          children: t.jsx(k, {
                                              variant: 'secondary',
                                              children: 'Cancel',
                                          }),
                                      }),
                                      t.jsx(k, {
                                          disabled: le,
                                          asChild: !0,
                                          children: t.jsx('button', {
                                              type: 'submit',
                                              children: 'Update Game',
                                          }),
                                      }),
                                  ],
                              }),
                          ],
                      });
                  },
              )),
          (e[42] = r),
          (e[43] = z),
          (e[44] = h))
        : (h = e[44]);
    let u;
    e[45] !== h
        ? ((u = t.jsxs(ye, { children: [W, h] })), (e[45] = h), (e[46] = u))
        : (u = e[46]);
    let x;
    e[47] !== I || e[48] !== u
        ? ((x = t.jsx(Ne, { open: I, onOpenChange: F, children: u })),
          (e[47] = I),
          (e[48] = u),
          (e[49] = x))
        : (x = e[49]);
    const V = B !== null;
    let G;
    e[50] === Symbol.for('react.memo_cache_sentinel')
        ? ((G = (s) => {
              s || Q(null);
          }),
          (e[50] = G))
        : (G = e[50]);
    let Y;
    e[51] === Symbol.for('react.memo_cache_sentinel')
        ? ((Y = t.jsx(Se, { children: 'Delete Game' })), (e[51] = Y))
        : (Y = e[51]);
    const se = B?.title;
    let T;
    e[52] !== se
        ? ((T = t.jsxs(be, {
              children: [
                  Y,
                  t.jsxs(ve, {
                      children: [
                          'Are you sure you want to delete',
                          ' ',
                          t.jsx('span', {
                              className: 'font-medium',
                              children: se,
                          }),
                          '? This action cannot be undone.',
                      ],
                  }),
              ],
          })),
          (e[52] = se),
          (e[53] = T))
        : (T = e[53]);
    let E;
    e[54] !== B
        ? ((E =
              B &&
              t.jsx(ge, {
                  ...xe.destroy.form(B.uuid),
                  onSuccess: () => Q(null),
                  children: dt,
              })),
          (e[54] = B),
          (e[55] = E))
        : (E = e[55]);
    let f;
    e[56] !== T || e[57] !== E
        ? ((f = t.jsxs(ye, { children: [T, E] })),
          (e[56] = T),
          (e[57] = E),
          (e[58] = f))
        : (f = e[58]);
    let p;
    e[59] !== V || e[60] !== f
        ? ((p = t.jsx(Ne, { open: V, onOpenChange: G, children: f })),
          (e[59] = V),
          (e[60] = f),
          (e[61] = p))
        : (p = e[61]);
    let L;
    return (
        e[62] !== d || e[63] !== w || e[64] !== x || e[65] !== p
            ? ((L = t.jsxs(Re, { breadcrumbs: at, children: [_, d, w, x, p] })),
              (e[62] = d),
              (e[63] = w),
              (e[64] = x),
              (e[65] = p),
              (e[66] = L))
            : (L = e[66]),
        L
    );
}
function dt(l) {
    const { processing: e, errors: a } = l;
    return t.jsxs(t.Fragment, {
        children: [
            t.jsx(ee, { message: a.game }),
            t.jsxs(Te, {
                className: 'gap-2',
                children: [
                    t.jsx(Ee, {
                        asChild: !0,
                        children: t.jsx(k, {
                            variant: 'secondary',
                            children: 'Cancel',
                        }),
                    }),
                    t.jsx(k, {
                        variant: 'destructive',
                        disabled: e,
                        asChild: !0,
                        children: t.jsx('button', {
                            type: 'submit',
                            children: 'Delete',
                        }),
                    }),
                ],
            }),
        ],
    });
}
function mt(l, e) {
    return t.jsx(
        k,
        {
            variant: l.active ? 'default' : 'outline',
            size: 'sm',
            disabled: l.url === null,
            asChild: l.url !== null,
            children:
                l.url !== null
                    ? t.jsx(Me, {
                          href: l.url,
                          dangerouslySetInnerHTML: { __html: l.label },
                      })
                    : t.jsx('span', {
                          dangerouslySetInnerHTML: { __html: l.label },
                      }),
        },
        e,
    );
}
export { Tt as default };
