import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;

// Reorders the [data-id] children of x-ref="list" by drag handle or Move up/down
// buttons, and persists the order to the given endpoint. Saves run one at a time.
Alpine.data('linkSorter', (url) => ({
    status: '',
    failed: false,
    queue: Promise.resolve(),

    init() {
        Sortable.create(this.$refs.list, {
            handle: '[data-sort-handle]',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: (event) => {
                if (event.oldIndex !== event.newIndex) {
                    this.save();
                }
            },
        });
    },

    move(button, offset) {
        const item = button.closest('[data-id]');
        const sibling = offset < 0 ? item.previousElementSibling : item.nextElementSibling;

        if (!sibling) {
            this.failed = false;
            this.status = offset < 0 ? 'Already at the top.' : 'Already at the bottom.';

            return;
        }

        offset < 0 ? sibling.before(item) : sibling.after(item);
        button.focus();
        this.save();
    },

    save() {
        this.failed = false;
        this.status = 'Saving order…';
        this.queue = this.queue.then(() => this.send());
    },

    async send() {
        const ids = [...this.$refs.list.querySelectorAll(':scope > [data-id]')].map((el) => Number(el.dataset.id));

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ links: ids }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            this.status = (await response.json()).message;
        } catch {
            this.failed = true;
            this.status = 'The new order could not be saved. Refresh the page and try again.';
        }
    },
}));

Alpine.start();
