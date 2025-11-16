
<!DOCTYPE html><html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certificates - Adds BD Limited</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>
  <script>
    // Disable right click globally
    document.addEventListener('contextmenu', event => event.preventDefault());function openLightbox(src){
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox').classList.remove('hidden');
}

function closeLightbox(){
  document.getElementById('lightbox').classList.add('hidden');
}

async function scanQRCodeFromImage(imgElement){
  const canvas = document.createElement('canvas');
  canvas.width = imgElement.naturalWidth;
  canvas.height = imgElement.naturalHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(imgElement, 0, 0);
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const code = jsQR(imageData.data, canvas.width, canvas.height);
  if(code){
    alert('QR Code Content: ' + code.data);
  } else {
    alert('QR কোড পাওয়া যায়নি।');
  }
}

  </script>
</head>
<body class="bg-gradient-to-br from-blue-900 via-indigo-800 to-blue-700 text-white min-h-screen select-none">
  <!-- Header -->
  <header class="text-center py-6 animate-pulse">
    <h1 class="text-3xl font-bold tracking-wide">Adds BD Limited</h1>
    <p class="text-gray-300 mt-2">✅ আমাদের অফিসিয়াল ডকুমেন্টস</p>
    <p class="text-sm italic text-gray-200">“আপনার বিশ্বাসই আমাদের শক্তি”</p>
  </header>  <!-- Certificates Section -->  <section class="px-4 sm:px-10 md:px-20">
    <div class="grid md:grid-cols-2 gap-6">
      <!-- E-Trade License -->
      <div class="bg-white/10 rounded-2xl shadow-lg p-4 backdrop-blur-md hover:scale-105 transform transition duration-300">
        <h2 class="text-xl font-semibold mb-3 flex items-center gap-2">📂 ই-ট্রেড লাইসেন্স</h2>
        <img id="cert1" src="/img/certificates.png" alt="E-Trade License" class="rounded-xl w-full border border-gray-400 shadow-md cursor-pointer hover:opacity-90 pointer-events-none" oncontextmenu="return false" draggable="false" onclick="openLightbox(this.src)"/>
        <button onclick="scanQRCodeFromImage(document.getElementById('cert1'))" class="mt-3 w-full py-2 rounded-lg bg-indigo-500 text-white font-medium">🔍 QR কোড স্ক্যান করুন</button>
      </div><!-- BIN Certificate -->
  <div class="bg-white/10 rounded-2xl shadow-lg p-4 backdrop-blur-md hover:scale-105 transform transition duration-300">
    <h2 class="text-xl font-semibold mb-3 flex items-center gap-2">📂 BIN সার্টিফিকেট</h2>
    <img id="cert2" src="/img/certificates.png" alt="BIN Certificate" class="rounded-xl w-full border border-gray-400 shadow-md cursor-pointer hover:opacity-90 pointer-events-none" oncontextmenu="return false" draggable="false" onclick="openLightbox(this.src)"/>
    <button onclick="scanQRCodeFromImage(document.getElementById('cert2'))" class="mt-3 w-full py-2 rounded-lg bg-indigo-500 text-white font-medium">📲 QR কোড যাচাই করুন</button>
  </div>
</div>

  </section>  <!-- Lightbox -->  <div id="lightbox" class="hidden fixed top-0 left-0 w-full h-full bg-black/80 flex items-center justify-center z-40" onclick="closeLightbox()">
    <img id="lightbox-img" class="max-w-full max-h-full rounded-xl" />
  </div>  <!-- নির্দেশনা Section -->  <section class="mt-10 px-6 md:px-20 text-center">
    <h3 class="text-2xl font-bold mb-4">📋 নির্দেশনা</h3>
    <p class="text-gray-200 leading-relaxed max-w-2xl mx-auto">
      বিনিয়োগের আগে ডকুমেন্টস যাচাই করুন! <br>
      যে প্রতিষ্ঠানে বিনিয়োগ করবেন, তাদের ডকুমেন্টে থাকা <b>QR কোড স্ক্যান করে সঠিকতা যাচাই</b> করুন।<br>
      <b>মুনাফা হাব</b> আপনাকে দিচ্ছে সহজ যাচাইয়ের সুবিধা। <br>
      ✨ আজই শুরু করুন আপনার বিনিয়োগ যাত্রা।
    </p>
  </section>  <!-- Back Button -->  <div class="mt-10 text-center">
    <a href="/user/dashboard" class="inline-block px-6 py-3 rounded-full bg-yellow-500 hover:bg-yellow-600 text-black font-semibold shadow-lg transition">⬅️ Back to Adds BD Limited</a>
  </div>  <!-- Trust Section -->  <section class="mt-12 text-center">
    <p class="text-lg font-medium text-yellow-300">🔒 Trusted by 10,000+ Investors</p>
    <p class="text-sm text-gray-300 mt-2">⭐ Verified by Govt. License</p>
  </section>  <!-- Footer -->  <footer class="mt-12 py-6 text-center text-gray-300 text-sm">
    © 2025 Addsbd Ltd. সর্বস্বত্ব সংরক্ষিত।
  </footer>
</body>
</html>