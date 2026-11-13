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

const HEX_COLOR = /^#[0-9a-f]{6}$/i;

function luminance(hex) {
    const [r, g, b] = [1, 3, 5].map((i) => {
        const c = parseInt(hex.slice(i, i + 2), 16) / 255;

        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrastRatio(a, b) {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (light + 0.05) / (dark + 0.05);
}

// Appearance editor state. Themes and shapes come from config/appearance.php; the
// preview reuses the public page markup and only swaps its --lg-* custom properties.
Alpine.data('appearanceEditor', (config) => ({
    themes: config.themes,
    shapes: config.shapes,
    theme: config.theme,
    values: { ...config.values },
    savedAvatar: config.avatar,
    avatar: config.removeAvatar ? null : config.avatar,
    pickedAvatar: null,
    avatarError: '',
    removeAvatar: Boolean(config.removeAvatar),
    notice: '',

    applyTheme(name) {
        const { label, ...defaults } = this.themes[name];
        this.values = defaults;
        this.notice = `Colors and button shape were reset to the ${label} theme defaults.`;
    },

    color(key) {
        const value = this.values[key];

        return HEX_COLOR.test(value) ? value : this.themes[this.theme][key];
    },

    get style() {
        const declarations = Object.keys(this.values)
            .filter((key) => key !== 'button_shape')
            .map((key) => `--lg-${key.replaceAll('_', '-')}: ${this.color(key)}`);
        const shape = this.shapes[this.values.button_shape] ?? this.shapes[this.themes[this.theme].button_shape];

        return [...declarations, `--lg-button-radius: ${shape.radius}`].join('; ');
    },

    get warnings() {
        const checks = [
            ['text_color', 'background_color', 'Text is hard to read against the background'],
            ['button_text_color', 'button_background_color', 'Button text is hard to read against the button background'],
        ];

        return checks
            .map(([fg, bg, message]) => [contrastRatio(this.color(fg), this.color(bg)), message])
            .filter(([ratio]) => ratio < 4.5)
            .map(([ratio, message]) => `${message} (${ratio.toFixed(1)}:1; aim for at least 4.5:1).`);
    },

    pickAvatar(event) {
        if (this.pickedAvatar) {
            URL.revokeObjectURL(this.pickedAvatar);
        }

        let file = event.target.files[0];
        this.avatarError = '';

        // Checked here as well as on the server: an oversized upload can exceed PHP's
        // post_max_size, which fails before validation can return a friendly message.
        if (file && file.size > config.avatarMaxBytes) {
            this.avatarError = 'That image is larger than 2 MB. Choose a smaller file.';
            event.target.value = '';
            file = null;
        }

        this.pickedAvatar = file ? URL.createObjectURL(file) : null;
        this.refreshAvatar();
    },

    refreshAvatar() {
        this.avatar = this.pickedAvatar ?? (this.removeAvatar ? null : this.savedAvatar);
    },
}));

Alpine.start();
