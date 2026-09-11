<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php
    $verifRequired = seller_verification_required();
    $oldType = htmlspecialchars($_POST['seller_type'] ?? ($seller_type ?? 'individual'));
    $oldBiz  = htmlspecialchars($_POST['business_name'] ?? ($business_name ?? ''));
    $oldCity = htmlspecialchars($_POST['city'] ?? ($city ?? ''));
?>
<div style="max-width:640px;margin:0 auto;padding:32px 16px 64px;">

  <!-- Welcome panel -->
  <div style="background: linear-gradient(135deg, var(--red) 0%, var(--red-deep) 100%); color:#fff; border-radius:16px; padding:24px; margin-bottom:24px;">
    <div style="display:inline-block;background:rgba(255,255,255,.2);color:#fff;font-family:var(--f-mono);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;padding:5px 12px;border-radius:999px;margin-bottom:14px;">★ Vendor Invitation</div>
    <div style="font-family:var(--f-display);font-weight:900;font-size:26px;line-height:1.1;text-transform:uppercase;letter-spacing:-0.02em;margin-bottom:8px;">Set up your store</div>
    <p style="font-family:var(--f-body);font-size:13px;color:rgba(255,255,255,.92);margin-bottom:14px;">Last step — tell us about your business and you're ready to list your first product.</p>
    <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;margin:0;padding:0;font-family:var(--f-mono);font-size:11px;">
      <li style="display:flex;align-items:center;gap:8px;"><span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;background:#fff;color:var(--red);border-radius:50%;font-weight:900;font-size:10px;">1</span> Choose your vendor type</li>
      <li style="display:flex;align-items:center;gap:8px;"><span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;background:#fff;color:var(--red);border-radius:50%;font-weight:900;font-size:10px;">2</span> Add your store name &amp; city</li>
      <li style="display:flex;align-items:center;gap:8px;"><span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;background:#fff;color:var(--red);border-radius:50%;font-weight:900;font-size:10px;">3</span> Verify your identity — get the badge</li>
    </ul>
  </div>

  <?php if (isset($error)): ?>
  <div style="background:#fffafa;border:1px solid #feeaea;color:var(--red);padding:16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;letter-spacing:.05em;border-radius:12px;margin-bottom:20px;">
      [ERROR] <?= $error ?>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:16px;">

    <!-- Section: About your business -->
    <div style="background:var(--paper);border:1px solid var(--light-gray);border-radius:16px;padding:20px;">
      <div style="font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--ink);margin-bottom:16px;">About your business</div>

      <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:16px;">Vendor Type
        <select name="seller_type" style="display:block;width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 14px;margin-top:8px;font-family:var(--f-mono);font-size:12px;color:var(--ink);outline:none;">
          <option value="individual" <?= $oldType==='individual'?'selected':'' ?>>Individual Seller (C2C)</option>
          <option value="business_retailer" <?= $oldType==='business_retailer'?'selected':'' ?>>Business / Retailer (B2C)</option>
          <option value="wholesaler" <?= $oldType==='wholesaler'?'selected':'' ?>>Wholesaler / Distributor (B2B)</option>
          <option value="manufacturer" <?= $oldType==='manufacturer'?'selected':'' ?>>Manufacturer (B2B)</option>
          <option value="international_supplier" <?= $oldType==='international_supplier'?'selected':'' ?>>International Supplier (B2B Export)</option>
        </select>
      </label>

      <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:16px;">Store / Business Name
        <input type="text" name="business_name" value="<?= $oldBiz ?>" placeholder="e.g. ABC Electronics Ghana" style="display:block;width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 14px;margin-top:8px;font-family:var(--f-mono);font-size:12px;outline:none;">
      </label>

      <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);">City
        <input type="text" name="city" value="<?= $oldCity ?>" placeholder="Accra, Kumasi..." style="display:block;width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 14px;margin-top:8px;font-family:var(--f-mono);font-size:12px;outline:none;">
      </label>
    </div>

    <!-- Section: Contact details -->
    <div style="background:var(--off);border:1px solid var(--light-gray);border-radius:16px;padding:20px;">
      <div style="font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--ink);margin-bottom:6px;">Store contact <span style="color:var(--red);">*</span></div>
      <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-bottom:14px;">Buyers use these to enquire about your products. Your WhatsApp is required.</div>

      <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:16px;">WhatsApp Number <span style="color:var(--red);">*</span>
        <input type="tel" name="whatsapp_number" value="<?= htmlspecialchars($_POST['whatsapp_number'] ?? '') ?>" placeholder="+233 24 000 0000" inputmode="tel" required style="display:block;width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 14px;margin-top:8px;font-family:var(--f-mono);font-size:12px;outline:none;">
      </label>

      <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);">WeChat ID <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span>
        <input type="text" name="wechat_id" value="<?= htmlspecialchars($_POST['wechat_id'] ?? '') ?>" placeholder="Your WeChat ID" autocomplete="off" style="display:block;width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 14px;margin-top:8px;font-family:var(--f-mono);font-size:12px;outline:none;">
      </label>
    </div>

    <!-- Section: Verification -->
    <div style="background:var(--paper);border:1px solid var(--light-gray);border-radius:16px;padding:20px;display:<?= $verifRequired ? 'block' : 'none' ?>;">
      <div style="font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--ink);margin-bottom:6px;">Verify your identity</div>
      <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-bottom:14px;">Ghana Card + a quick face check earn you the <strong style="color:var(--ink);">✓ Verified Vendor</strong> badge — buyers trust verified stores more.</div>

      <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:16px;">Ghana Card (image, front) <span style="color:var(--red);">*</span>
        <input type="file" name="ghana_card" accept="image/*" <?= $verifRequired ? 'required' : '' ?> style="display:block;width:100%;padding:12px;border:1px solid var(--light-gray);border-radius:12px;background:#fff;margin-top:8px;font-family:var(--f-mono);font-size:11px;">
      </label>

      <div style="font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);">Face check <span style="color:var(--red);">*</span> <span style="font-weight:400;text-transform:none;letter-spacing:0;">— allow camera, center your face, capture</span></div>
      <div style="margin-top:10px;display:flex;gap:14px;flex-wrap:wrap;align-items:start;">
        <div style="flex:1;min-width:220px;">
          <video id="face-video" autoplay playsinline muted style="width:100%;max-width:320px;height:240px;background:#000;border-radius:12px;border:1px solid var(--light-gray);object-fit:cover;display:none;"></video>
          <canvas id="face-canvas" style="display:none;"></canvas>
          <div style="display:flex;gap:8px;margin-top:10px;">
            <button type="button" id="face-start" style="flex:1;height:42px;background:var(--ink);color:#fff;border:none;border-radius:10px;font-family:var(--f-mono);font-size:11px;font-weight:700;cursor:pointer;">Start Camera</button>
            <button type="button" id="face-capture" style="flex:1;height:42px;background:var(--red);color:#fff;border:none;border-radius:10px;font-family:var(--f-mono);font-size:11px;font-weight:700;cursor:pointer;display:none;">Capture</button>
            <button type="button" id="face-retake" style="flex:1;height:42px;background:#fff;color:var(--ink);border:1.5px solid var(--ink);border-radius:10px;font-family:var(--f-mono);font-size:11px;font-weight:700;cursor:pointer;display:none;">Retake</button>
          </div>
        </div>
        <div style="flex:0 0 140px;">
          <div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:.06em;">Preview</div>
          <img id="face-preview" style="width:140px;height:140px;object-fit:cover;border-radius:12px;border:1px solid var(--light-gray);background:var(--off);margin-top:6px;display:none;" alt="Face preview">
          <div id="face-status" style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-top:6px;">No capture yet</div>
        </div>
      </div>
      <input type="hidden" name="face_data" id="face-data">
      <div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);margin-top:10px;">Must match the person on your Ghana Card. Images are used for verification only.</div>
    </div>

    <?= Csrf::field() ?>
    <button type="submit" id="seller-submit" style="height:52px;background:linear-gradient(135deg, var(--red) 0%, var(--red-deep) 100%);color:#fff;font-family:var(--f-display);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.08em;border:none;border-radius:12px;cursor:pointer;box-shadow:0 4px 14px rgba(232,0,45,.25);">Launch My Store →</button>
    <p style="text-align:center;font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);margin-top:-6px;">Free to list • You can update store details anytime from settings</p>
  </form>

  <script>
  (function(){
    const video=document.getElementById('face-video'), canvas=document.getElementById('face-canvas'), preview=document.getElementById('face-preview'), statusEl=document.getElementById('face-status'), faceData=document.getElementById('face-data');
    const btnStart=document.getElementById('face-start'), btnCapture=document.getElementById('face-capture'), btnRetake=document.getElementById('face-retake'), submitBtn=document.getElementById('seller-submit');
    let stream=null;
    btnStart?.addEventListener('click', async ()=>{
      try{
        stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:640,height:480},audio:false});
        video.srcObject=stream; video.style.display='block'; btnStart.style.display='none'; btnCapture.style.display='block'; statusEl.textContent='Camera on — capture face';
        statusEl.style.color='var(--mid-gray)';
      }catch(e){ statusEl.textContent='Camera blocked — allow access or use file upload fallback'; statusEl.style.color='var(--red)'; }
    });
    btnCapture?.addEventListener('click', ()=>{
      if(!video.videoWidth) return;
      canvas.width=video.videoWidth; canvas.height=video.videoHeight;
      const ctx=canvas.getContext('2d'); ctx.drawImage(video,0,0);
      const dataUrl=canvas.toDataURL('image/jpeg',0.85);
      faceData.value=dataUrl; preview.src=dataUrl; preview.style.display='block';
      statusEl.textContent='Captured ✓'; statusEl.style.color='#16a34a';
      btnCapture.style.display='none'; btnRetake.style.display='block';
      if(stream){ stream.getTracks().forEach(t=>t.stop()); video.style.display='none'; }
    });
    btnRetake?.addEventListener('click', ()=>{
      faceData.value=''; preview.style.display='none'; preview.src=''; statusEl.textContent='Retake — start camera again'; statusEl.style.color='var(--mid-gray)';
      btnRetake.style.display='none'; btnStart.style.display='block';
    });
    // Block submit until face captured (only when verification is required)
    const form=document.querySelector('form');
    form?.addEventListener('submit', (e)=>{
      if(<?= $verifRequired ? 'true' : 'false' ?> && !faceData.value){
        e.preventDefault();
        statusEl.textContent='Face capture required'; statusEl.style.color='var(--red)';
        document.getElementById('face-video')?.scrollIntoView({behavior:'smooth',block:'center'});
      }
    });
  })();
  </script>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
