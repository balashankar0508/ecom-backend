// resources/js/products.js
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('title');
    const categorySelect = document.getElementById('category_id');
    const slugInput = document.getElementById('slug');

    if (!titleInput) return;

    // small slug generator
    function slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
    }

    titleInput.addEventListener('input', function () {
        // generate slug live
        if (slugInput && !slugInput.value) {
            slugInput.value = slugify(titleInput.value);
        }

        // quick auto category selection rules (client-side)
        const t = titleInput.value.toLowerCase();
        const rules = [
            { keywords: ['shirt','t-shirt','jeans','dress'], slug: 'fashion' },
            { keywords: ['organic','bio','aloe','herbal'], slug: 'organic' },
            { keywords: ['phone','laptop','tv','earbud'], slug: 'electronics' },
        ];

        for (const rule of rules) {
            if (rule.keywords.some(k => t.includes(k))) {
                // find option with slug text in textContent (server rendered options include names only)
                // We rely on category ids in server; better approach: embed slug->id map as JSON from blade.
                const opt = Array.from(categorySelect.options).find(o => o.text.toLowerCase().includes(rule.slug));
                if (opt) {
                    categorySelect.value = opt.value;
                }
                break;
            }
        }
    });
});
