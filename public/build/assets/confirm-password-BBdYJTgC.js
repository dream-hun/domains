import { c as t, j as s, F as a, H as i } from './app-C_M1-u2r.js';
import { I as m } from './input-error-CkKI5kvP.js';
import { B as n } from './app-logo-icon-BcSq9Noa.js';
import { I as p } from './input-Cn0z7F4k.js';
import { L as c } from './label-CKqDLf1o.js';
import { S as l } from './spinner-C6urz_Om.js';
import { A as d } from './auth-split-layout-C3vCIXfX.js';
import { s as f } from './index-B0Z-ysPe.js';
/* empty css            */ import './index-m3SvSfPp.js';
import './loader-circle-DQQAFbLc.js';
import './index-CSEUMRIG.js';
function N() {
    const r = t.c(2);
    let o;
    r[0] === Symbol.for('react.memo_cache_sentinel')
        ? ((o = s.jsx(i, { title: 'Confirm password' })), (r[0] = o))
        : (o = r[0]);
    let e;
    return (
        r[1] === Symbol.for('react.memo_cache_sentinel')
            ? ((e = s.jsxs(d, {
                  title: 'Confirm your password',
                  description:
                      'This is a secure area of the application. Please confirm your password before continuing.',
                  children: [
                      o,
                      s.jsx(a, {
                          ...f.form(),
                          resetOnSuccess: ['password'],
                          children: u,
                      }),
                  ],
              })),
              (r[1] = e))
            : (e = r[1]),
        e
    );
}
function u(r) {
    const { processing: o, errors: e } = r;
    return s.jsxs('div', {
        className: 'space-y-6',
        children: [
            s.jsxs('div', {
                className: 'grid gap-2',
                children: [
                    s.jsx(c, { htmlFor: 'password', children: 'Password' }),
                    s.jsx(p, {
                        id: 'password',
                        type: 'password',
                        name: 'password',
                        placeholder: 'Password',
                        autoComplete: 'current-password',
                        autoFocus: !0,
                    }),
                    s.jsx(m, { message: e.password }),
                ],
            }),
            s.jsx('div', {
                className: 'flex items-center',
                children: s.jsxs(n, {
                    className: 'w-full',
                    disabled: o,
                    'data-test': 'confirm-password-button',
                    children: [o && s.jsx(l, {}), 'Confirm password'],
                }),
            }),
        ],
    });
}
export { N as default };
