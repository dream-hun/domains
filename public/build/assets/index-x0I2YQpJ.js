import { q as m } from './index-CSEUMRIG.js';
const r = (e) => ({ url: r.url(e), method: 'get' });
r.definition = { methods: ['get', 'head'], url: '/login' };
r.url = (e) => r.definition.url + m(e);
r.get = (e) => ({ url: r.url(e), method: 'get' });
r.head = (e) => ({ url: r.url(e), method: 'head' });
const h = (e) => ({ action: r.url(e), method: 'get' });
h.get = (e) => ({ action: r.url(e), method: 'get' });
h.head = (e) => ({
    action: r.url({
        [e?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
r.form = h;
const l = (e) => ({ url: l.url(e), method: 'post' });
l.definition = { methods: ['post'], url: '/logout' };
l.url = (e) => l.definition.url + m(e);
l.post = (e) => ({ url: l.url(e), method: 'post' });
const c = (e) => ({ action: l.url(e), method: 'post' });
c.post = (e) => ({ action: l.url(e), method: 'post' });
l.form = c;
const t = (e) => ({ url: t.url(e), method: 'get' });
t.definition = { methods: ['get', 'head'], url: '/register' };
t.url = (e) => t.definition.url + m(e);
t.get = (e) => ({ url: t.url(e), method: 'get' });
t.head = (e) => ({ url: t.url(e), method: 'head' });
const g = (e) => ({ action: t.url(e), method: 'get' });
g.get = (e) => ({ action: t.url(e), method: 'get' });
g.head = (e) => ({
    action: t.url({
        [e?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
t.form = g;
const o = (e) => ({ url: o.url(e), method: 'get' });
o.definition = { methods: ['get', 'head'], url: '/' };
o.url = (e) => o.definition.url + m(e);
o.get = (e) => ({ url: o.url(e), method: 'get' });
o.head = (e) => ({ url: o.url(e), method: 'head' });
const a = (e) => ({ action: o.url(e), method: 'get' });
a.get = (e) => ({ action: o.url(e), method: 'get' });
a.head = (e) => ({
    action: o.url({
        [e?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
o.form = a;
const u = (e) => ({ url: u.url(e), method: 'get' });
u.definition = { methods: ['get', 'head'], url: '/dashboard' };
u.url = (e) => u.definition.url + m(e);
u.get = (e) => ({ url: u.url(e), method: 'get' });
u.head = (e) => ({ url: u.url(e), method: 'head' });
const n = (e) => ({ action: u.url(e), method: 'get' });
n.get = (e) => ({ action: u.url(e), method: 'get' });
n.head = (e) => ({
    action: u.url({
        [e?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
u.form = n;
const d = (e) => ({ url: d.url(e), method: 'get' });
d.definition = { methods: ['get', 'head'], url: '/leaderboard' };
d.url = (e) => d.definition.url + m(e);
d.get = (e) => ({ url: d.url(e), method: 'get' });
d.head = (e) => ({ url: d.url(e), method: 'head' });
const i = (e) => ({ action: d.url(e), method: 'get' });
i.get = (e) => ({ action: d.url(e), method: 'get' });
i.head = (e) => ({
    action: d.url({
        [e?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
d.form = i;
export { l as a, d as b, u as d, r as l, t as r };
