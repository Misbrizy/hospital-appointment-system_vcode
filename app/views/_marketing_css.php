<style>
.hero{background:linear-gradient(180deg,var(--teal-deep),#164B4C);color:#fff;padding:calc(64px + var(--safe-t)) 24px 84px}
.hero-inner{max-width:1060px;margin:0 auto;display:grid;grid-template-columns:1.1fr .9fr;gap:44px;align-items:center}
@media (max-width:860px){.hero-inner{grid-template-columns:1fr}}
.hero h1{color:#fff;font-size:2.3rem;max-width:520px}
.hero p.lead{color:#CFE6E2;font-size:1.08rem;max-width:480px}
.hero .cta{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
.page-hero{background:linear-gradient(180deg,var(--teal-deep),#164B4C);color:#fff;padding:calc(48px + var(--safe-t)) 24px 48px;text-align:center}
.page-hero h1{color:#fff;font-size:2rem;max-width:620px;margin:0 auto}
.page-hero p{color:#CFE6E2;max-width:560px;margin:10px auto 0}
.btn.white{background:#fff;color:var(--teal-deep);border-color:#fff}
.btn.white:hover{background:#EAF4F2}
.btn.outline{background:transparent;color:#fff;border-color:rgba(255,255,255,.55)}
.btn.outline:hover{background:rgba(255,255,255,.1)}
.phone-mock{background:#0B2C2D;border-radius:26px;padding:14px;box-shadow:0 20px 50px -20px rgba(0,0,0,.5);max-width:300px;margin:0 auto}
.phone-mock .screen{background:#F4F7F4;border-radius:16px;padding:16px 14px 20px;min-height:340px}
.phone-mock .app-head{font-family:var(--serif);font-weight:700;color:var(--teal-deep);font-size:.95rem;margin-bottom:10px}
nav.topnav{max-width:1060px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding-top:var(--safe-t);flex-wrap:wrap;gap:10px}
nav.topnav .brand{display:flex;align-items:center;gap:9px}
nav.topnav .brand .mark{width:30px;height:30px;border-radius:8px;background:var(--clay);color:#fff;display:grid;place-items:center;font-family:var(--serif);font-weight:700}
nav.topnav .brand span{font-family:var(--serif);font-weight:600;font-size:1.15rem;color:#fff}
nav.topnav .links{display:flex;align-items:center;flex-wrap:wrap}
nav.topnav .links a{color:#CFE6E2;margin-left:18px;font-size:.92rem;font-weight:500}
nav.topnav .links a:hover{color:#fff}
nav.topnav .links a.active{color:#fff;text-decoration:underline}
section{max-width:1060px;margin:0 auto;padding:64px 24px}
.section-title{text-align:center;max-width:560px;margin:0 auto 40px}
.feat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:22px}
.feat{border:1px solid var(--line-soft);border-radius:var(--radius);padding:20px;background:var(--paper-raised)}
.feat .ic{width:38px;height:38px;border-radius:9px;background:var(--teal-wash);color:var(--teal-deep);display:grid;place-items:center;margin-bottom:12px}
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;counter-reset:step}
@media (max-width:860px){.steps{grid-template-columns:1fr 1fr}}
.step{counter-increment:step;position:relative;padding-top:8px}
.step::before{content:counter(step);width:30px;height:30px;border-radius:50%;background:var(--clay);color:#fff;display:grid;place-items:center;font-weight:700;font-size:.85rem;margin-bottom:10px}
.pricing{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:20px}
.plan{border:1.5px solid var(--line);border-radius:var(--radius);padding:24px;background:var(--paper-raised)}
.plan.pop{border-color:var(--clay);box-shadow:var(--shadow)}
.plan .tag{display:inline-block;background:var(--clay-wash);color:var(--clay);font-size:.72rem;font-weight:700;padding:2px 9px;border-radius:100px;margin-bottom:10px}
.plan .price{font-family:var(--serif);font-size:1.7rem;color:var(--teal-deep);margin:6px 0}
.plan ul{padding-left:18px;margin:14px 0}
.plan li{margin-bottom:6px;font-size:.9rem}
footer{background:var(--teal-deep);color:#B9D7D2;padding:32px 24px;text-align:center;font-size:.86rem}
footer a{color:#DCEEEA}
.benefit{display:flex;gap:14px}
.benefit .ic{width:36px;height:36px;flex:none;border-radius:9px;background:var(--leaf-wash);color:var(--leaf);display:grid;place-items:center}

/* Sign-in choice dialog */
.signin-overlay{display:none;position:fixed;inset:0;background:rgba(15,61,62,.55);z-index:100;align-items:center;justify-content:center;padding:20px}
.signin-overlay.open{display:flex}
.signin-dialog{background:#fff;border-radius:14px;max-width:440px;width:100%;padding:28px;box-shadow:0 30px 70px -20px rgba(0,0,0,.4)}
.signin-dialog h2{margin-top:0}
.signin-choice{display:flex;gap:14px;flex-wrap:wrap;margin-top:18px}
.signin-card{flex:1;min-width:160px;border:1.5px solid var(--line);border-radius:var(--radius);padding:18px 14px;text-align:center;cursor:pointer;transition:border-color .15s}
.signin-card:hover{border-color:var(--teal);background:var(--teal-wash)}
.signin-card .ic{width:40px;height:40px;border-radius:10px;background:var(--teal-wash);color:var(--teal-deep);display:grid;place-items:center;margin:0 auto 10px}
.signin-card h3{font-size:.98rem;margin:0 0 4px}
.signin-card p{font-size:.78rem;color:var(--ink-soft);margin:0}
.signin-close{position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.3rem;color:var(--ink-soft);cursor:pointer}
</style>
