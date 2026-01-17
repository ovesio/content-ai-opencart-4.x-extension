document.addEventListener('DOMContentLoaded', function() {
    if (typeof ovesioConfig !== 'undefined') {
        const targetContainer = document.querySelector('.float-end');
        if (targetContainer) {
            // Helper to create button
            const createButton = (type, color) => {
                if (ovesioConfig.status[type]) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn';
                    btn.setAttribute('data-resource', ovesioConfig.resource);
                    btn.setAttribute('data-route', ovesioConfig.route);
                    btn.setAttribute('data-href', ovesioConfig.manualUrl);
                    btn.style.background = color;
                    btn.style.color = 'white';
                    btn.style.fontWeight = 'bold';
                    btn.style.marginLeft = '5px';

                    if (type === 'content') {
                        btn.onclick = function(e) { ovesio.generateContent(e); };
                        btn.innerText = ovesioConfig.text.content;
                    } else if (type === 'seo') {
                        btn.onclick = function(e) { ovesio.generateSeo(e); };
                        btn.innerText = ovesioConfig.text.seo;
                    } else if (type === 'translate') {
                        btn.onclick = function(e) { ovesio.translate(e); };
                        btn.innerText = ovesioConfig.text.translate;
                    }

                    // Insert before the last button (usually "Add New" or "Delete")?
                    // Or just append? Standard OC "Add New" is usually first or last depending on theme.
                    // We append to the container.
                    targetContainer.insertBefore(btn, targetContainer.firstChild);
                }
            };

            // Create buttons in reverse order of desired appearance if using prepend/insertBefore
            createButton('translate', '#198754');
            createButton('seo', '#ffc107');
            createButton('content', '#0dcaf0');
        }
    }
});
