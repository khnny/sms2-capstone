<div id="rscSigModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.68);overflow:auto;padding:2rem 1rem;">
    <div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#065f46 0%,#047857 55%,#059669 100%);padding:1rem 1.4rem;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="color:rgba(209,250,229,.8);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem;">Research Services Clearance</div>
                <h3 style="margin:0;color:#fff;font-size:1.05rem;font-weight:800;"><?= smsIcon('signature', ['class' => 'me-2']) ?>Draw Your Signature</h3>
            </div>
            <button type="button" data-rsc-sig-close style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:8px;padding:.35rem .8rem;cursor:pointer;font-weight:700;font-size:.82rem;">
                <?= smsIcon('times') ?>
            </button>
        </div>
        <div style="padding:1.25rem;">
            <p style="margin:0 0 .75rem;font-size:.82rem;color:#374151;font-weight:600;">
                Sign in the box below. Your signature will be saved to the clearance form.
            </p>
            <div style="border:2px solid #d1d5db;border-radius:8px;background:#f9fafb;position:relative;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.4rem .75rem;background:#f3f4f6;border-bottom:1px solid #e5e7eb;">
                    <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;"><?= e($rscSigPadLabel ?? 'Signature Pad (Draw Below)') ?></span>
                    <button type="button" id="rscSigClear" style="background:none;border:none;color:#7c3aed;font-size:.75rem;font-weight:800;cursor:pointer;padding:0;">Clear Pad</button>
                </div>
                <canvas id="rscSigCanvas" style="display:block;width:100%;height:160px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
            </div>
            <div id="rscSigError" style="display:none;margin-top:.6rem;padding:.5rem .75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;font-size:.8rem;font-weight:700;">
                <?= smsIcon('exclamation-circle', ['class' => 'me-1']) ?>Please provide your signature before approving.
            </div>
        </div>
        <div style="padding:.85rem 1.25rem;border-top:1px solid #e5e7eb;display:flex;align-items:center;justify-content:flex-end;gap:.65rem;background:#f9fafb;">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-rsc-sig-close>Cancel</button>
            <button type="button" class="btn btn-success btn-sm" id="rscSigConfirm">Confirm &amp; Approve</button>
        </div>
    </div>
</div>
