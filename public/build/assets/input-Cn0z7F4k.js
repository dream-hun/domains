import { c as l, j as a } from './app-C_M1-u2r.js';
import { c as d } from './app-logo-icon-BcSq9Noa.js';
function u(s) {
    const e = l.c(10);
    let i, r, t;
    e[0] !== s
        ? (({ className: i, type: t, ...r } = s),
          (e[0] = s),
          (e[1] = i),
          (e[2] = r),
          (e[3] = t))
        : ((i = e[1]), (r = e[2]), (t = e[3]));
    let o;
    e[4] !== i
        ? ((o = d(
              'border-input file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
              'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
              'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
              i,
          )),
          (e[4] = i),
          (e[5] = o))
        : (o = e[5]);
    let n;
    return (
        e[6] !== r || e[7] !== o || e[8] !== t
            ? ((n = a.jsx('input', {
                  type: t,
                  'data-slot': 'input',
                  className: o,
                  ...r,
              })),
              (e[6] = r),
              (e[7] = o),
              (e[8] = t),
              (e[9] = n))
            : (n = e[9]),
        n
    );
}
export { u as I };
