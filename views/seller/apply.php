<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<div style="max-width:640px;margin:0 auto;padding:32px 16px;">
  <h1 style="font-family:var(--f-display);font-weight:900;">Become a Seller on Avazonia</h1>
  <p style="color:var(--mid-gray);">Choose your seller type. Free listing at launch. Verification gives you a badge.</p>
  <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:14px;margin-top:18px;">
    <?= Csrf::field() ?>
    <label style="font-family:var(--f-mono);font-size:11px;">Seller Type
      <select name="seller_type" style="width:100%;height:44px;border:1px solid var(--light-gray);padding:0 12px;margin-top:6px;">
        <option value="individual">Individual Seller (C2C)</option>
        <option value="business_retailer">Business / Retailer (B2C)</option>
        <option value="wholesaler">Wholesaler / Distributor (B2B)</option>
        <option value="manufacturer">Manufacturer (B2B)</option>
        <option value="international_supplier">International Supplier (B2B Export)</option>
      </select>
    </label>
    <label style="font-family:var(--f-mono);font-size:11px;">Business / Display Name <input type="text" name="business_name" placeholder="e.g. ABC Electronics Ghana" style="width:100%;height:44px;border:1px solid var(--light-gray);padding:0 12px;margin-top:6px;"></label>
    <label style="font-family:var(--f-mono);font-size:11px;">City <input type="text" name="city" placeholder="Accra, Kumasi..." style="width:100%;height:44px;border:1px solid var(--light-gray);padding:0 12px;margin-top:6px;"></label>
    <?php $verifRequired = seller_verification_required(); ?>
    <div style="border:1.5px solid var(--ink);padding:16px;background:var(--paper);display:<?= $verifRequired ? 'block' : 'none' ?>;">
      <div style="font-family:var(--f-mono);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">Verification — Ghana Card + Face ID</div>
      <label style="font-family:var(--f-mono);font-size:11px;">Ghana Card (image, front) <span style="color:var(--red);">*</span> <input type="file" name="ghana_card" accept="image/*" <?= $verifRequired ? 'required' : '' ?> style="width:100%;padding:10px;border:1px solid var(--light-gray);margin-top:6px;"></label>
      <div style="margin-top:14px;">
        <div style="font-family:var(--f-mono);font-size:11px;">Face ID — capture via camera <span style="color:var(--red);">*</span> <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--mid-gray);">(allow camera, center face, capture)</span></div>
        <div style="margin-top:8px;display:flex;gap:12px;flex-wrap:wrap;align-items:start;">
          <div style="flex:1;min-width:220px;">
            <video id="face-video" autoplay playsinline muted style="width:100%;max-width:320px;height:240px;background:#000;border:1px solid var(--light-gray);object-fit:cover;display:none;"></video>
            <canvas id="face-canvas" style="display:none;"></canvas>
            <div style="display:flex;gap:8px;margin-top:8px;">
              <button type="button" id="face-start" style="flex:1;height:36px;background:var(--ink);color:#fff;border:none;font-family:var(--f-mono);font-size:11px;font-weight:700;cursor:pointer;">Start Camera</button>
              <button type="button" id="face-capture" style="flex:1;height:36px;background:var(--red);color:#fff;border:none;font-family:var(--f-mono);font-size:11px;font-weight:700;cursor:pointer;display:none;">Capture</button>
              <button type="button" id="face-retake" style="flex:1;height:36px;background:#fff;color:var(--ink);border:1px solid var(--ink);font-family:var(--f-mono);font-size:11px;font-weight:700;cursor:pointer;display:none;">Retake</button>
            </div>
          </div>
          <div style="flex:0 0 140px;">
            <div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:.06em;">Preview</div>
            <img id="face-preview" style="width:140px;height:140px;object-fit:cover;border:1px solid var(--light-gray);background:var(--off);margin-top:6px;display:none;" alt="Face preview">
            <div id="face-status" style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-top:6px;">No capture yet</div>
          </div>
        </div>
        <input type="hidden" name="face_data" id="face-data">
        <div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);margin-top:8px;">Same person as Ghana Card. Camera data stays on server for verification only.</div>
      </div>
    </div>
    <button type="submit" id="seller-submit" style="height:48px;background:var(--ink);color:#fff;font-weight:800;text-transform:uppercase;border:none;cursor:pointer;">Create Seller Profile</button>
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
  </form>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
