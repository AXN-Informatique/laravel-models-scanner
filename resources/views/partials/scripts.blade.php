<script>
    document.querySelectorAll('[data-relation-code]').forEach((container) => {
        // Copier le code d'une relation
        container.addEventListener('click', async (event) => {
            const el = event.target.closest('[data-relation-code]');
            if (! el) return;

            const relationCode = el.dataset.relationCode;
            if (! relationCode) return;

            try {
                await navigator.clipboard.writeText(relationCode);

                el.classList.add('copied');
                setTimeout(() => el.classList.remove('copied'), 500);

            } catch (err) {
                console.error('Copie impossible :', err);
                alert('Impossible de copier : vérifier HTTPS et permissions.');
            }
        });

        // Copier le code de toutes les relations d'un modèle
        container.addEventListener('contextmenu', async (event) => {
            const body = event.target.closest('.body');
            if (! body) return;

            event.preventDefault();

            let allRelationsCode = '';

            body.querySelectorAll('[data-relation-code]').forEach((el) => {
                const relationCode = el.dataset.relationCode;
                if (! relationCode) return;

                allRelationsCode += (allRelationsCode !== '' ? "\n" : '')+relationCode;
            });

            try {
                await navigator.clipboard.writeText(allRelationsCode);

                body.querySelectorAll('[data-relation-code]').forEach((el) => {
                    el.classList.add('copied');
                    setTimeout(() => el.classList.remove('copied'), 500);
                });

            } catch (err) {
                console.error('Copie impossible :', err);
                alert('Impossible de copier : vérifier HTTPS et permissions.');
            }
        });
    });
</script>
