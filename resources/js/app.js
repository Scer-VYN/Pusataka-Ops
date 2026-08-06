const books = window.libraryBooks ?? {};
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const notificationsDisabled = window.libraryPreferences?.getNotificationsEnabled?.() === false;

if (notificationsDisabled) {
    document.querySelector('#notification-trigger')?.setAttribute('hidden', '');
    document.querySelector('#notification-popover')?.setAttribute('hidden', '');
    document.querySelector('[data-nav="notifications"]')?.setAttribute('hidden', '');
}

const modal = document.querySelector('#book-modal');
const modalCover = document.querySelector('#modal-cover');
const modalCoverTitle = document.querySelector('#modal-cover-title');
const modalDetailLink = document.querySelector('#modal-detail-link');
const toast = document.querySelector('#toast');
let toastTimeout;
let lastFocusedElement;

function showToast(message) {
    const toastMessage = document.querySelector('#toast-message');

    if (!toast || !toastMessage) return;

    toastMessage.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(toastTimeout);
    toastTimeout = window.setTimeout(() => toast.classList.remove('show'), 3200);
}

function validateBorrowForm(dateInput, durationInput, errorElement) {
    const errors = [];
    const today = dateInput.min || new Date().toISOString().slice(0, 10);
    const allowedDurations = ['7', '14', '21', '30'];

    if (!dateInput.value) {
        errors.push('Select a start date.');
        dateInput.setAttribute('aria-invalid', 'true');
    } else if (dateInput.value < today) {
        errors.push('Start date cannot be in the past.');
        dateInput.setAttribute('aria-invalid', 'true');
    } else {
        dateInput.setAttribute('aria-invalid', 'false');
    }

    if (!allowedDurations.includes(durationInput.value)) {
        errors.push('Select a valid borrowing duration.');
        durationInput.setAttribute('aria-invalid', 'true');
    } else {
        durationInput.setAttribute('aria-invalid', 'false');
    }

    errorElement.textContent = errors.join(' ');
    errorElement.hidden = errors.length === 0;

    return errors.length === 0;
}

function openBook(bookId) {
    const book = books[bookId];
    if (!book || !modal || !modalCover) return;

    lastFocusedElement = document.activeElement;
    document.querySelector('#modal-category').textContent = book.category;
    document.querySelector('#modal-title').textContent = book.title;
    document.querySelector('#modal-author').textContent = book.author;
    document.querySelector('#modal-publisher').textContent = book.publisher;
    document.querySelector('#modal-location').textContent = book.location;
    document.querySelector('#modal-availability').textContent = book.availability;
    document.querySelector('#borrow-summary-title').textContent = book.title;
    document.querySelector('.modal-description').textContent = book.description;
    modalCover.className = `modal-cover ${book.cover}`;
    if (modalCoverTitle) modalCoverTitle.textContent = book.title.toUpperCase();
    if (modalDetailLink) modalDetailLink.href = book.detail_url;
    modalCover.dataset.book = bookId;
    const borrowButton = document.querySelector('#modal-borrow');
    if (borrowButton) {
        borrowButton.disabled = !book.available;
        borrowButton.setAttribute('aria-disabled', String(!book.available));
        borrowButton.querySelector('span').textContent = book.available
            ? 'Request to borrow'
            : 'Currently unavailable';
    }
    updateBorrowSummary();
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.querySelector('#modal-close')?.focus();
}

function updateBorrowSummary() {
    const startDate = document.querySelector('#modal-borrow-date')?.value;
    const duration = Number.parseInt(document.querySelector('#modal-borrow-duration')?.value ?? '', 10);
    const summaryDates = document.querySelector('#borrow-summary-dates');

    if (!startDate || !Number.isInteger(duration) || !summaryDates) {
        return;
    }

    const dueDate = new Date(`${startDate}T00:00:00`);
    dueDate.setDate(dueDate.getDate() + duration);
    const dueDateString = [
        dueDate.getFullYear(),
        String(dueDate.getMonth() + 1).padStart(2, '0'),
        String(dueDate.getDate()).padStart(2, '0'),
    ].join('-');
    summaryDates.textContent = `START ${startDate} // DUE ${dueDateString}`;
}

function closeBook() {
    if (!modal) return;

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    if (lastFocusedElement instanceof HTMLElement && document.contains(lastFocusedElement)) {
        lastFocusedElement.focus();
    }
    lastFocusedElement = null;
}

async function postJson(url, payload = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new Error(Object.values(error.errors ?? {})[0]?.[0] ?? 'The request could not be completed.');
    }

    return response.json();
}

document.querySelectorAll('.details-link').forEach((button) => {
    button.addEventListener('click', () => openBook(button.dataset.book));
});

document.querySelector('#modal-close')?.addEventListener('click', closeBook);
document.querySelector('#modal-borrow-date')?.addEventListener('change', updateBorrowSummary);
document.querySelector('#modal-borrow-duration')?.addEventListener('change', updateBorrowSummary);
modal?.addEventListener('click', (event) => {
    if (event.target === modal) closeBook();
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeBook();
        closeNotificationPopover();
        closeProfileMenu();
        setSidebarOpen(false);
    }
});

document.querySelector('#modal-borrow')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const bookId = modalCover?.dataset.book;

    if (!validateBorrowForm(
        document.querySelector('#modal-borrow-date'),
        document.querySelector('#modal-borrow-duration'),
        document.querySelector('#modal-borrow-error'),
    )) {
        return;
    }

    button.disabled = true;

    try {
        if (!window.confirm('Confirm this borrowing request?')) {
            return;
        }

        const result = await postJson('/borrowings', {
            book_id: bookId,
            borrow_date: document.querySelector('#modal-borrow-date')?.value,
            duration: document.querySelector('#modal-borrow-duration')?.value,
        });
        closeBook();
        showToast(result.message);
        window.setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast(error.message);
    } finally {
        button.disabled = false;
    }
});

document.querySelector('#extend-button')?.addEventListener('click', async (event) => {
    const borrowingId = event.currentTarget.dataset.borrowingId;

    if (!borrowingId) {
        showToast('There is no active borrowing to extend.');
        return;
    }

    try {
        const result = await postJson(`/borrowings/${borrowingId}/extend`);
        showToast(result.message);
        window.setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast(error.message);
    }
});

document.querySelectorAll('.bookmark-button').forEach((button) => {
    button.addEventListener('click', async () => {
        const wasSaved = button.classList.contains('saved');
        button.disabled = true;

        try {
            const result = await postJson(`/books/${button.dataset.bookId}/saved`);
            button.classList.toggle('saved', result.saved);
            button.textContent = result.saved ? '★' : '☆';
            showToast(result.saved ? 'Title added to your saved list.' : 'Title removed from your saved list.');
        } catch (error) {
            showToast(error.message);
            button.classList.toggle('saved', wasSaved);
            button.textContent = wasSaved ? '★' : '☆';
        } finally {
            button.disabled = false;
        }
    });
});

const cards = [...document.querySelectorAll('.book-card')];
const filterButtons = [...document.querySelectorAll('.filter-chip')];
const emptyState = document.querySelector('#empty-state');
const searchInput = document.querySelector('#global-search');
let selectedFilter = 'all';

function renderBooks() {
    if (!searchInput || !emptyState) return;

    const searchTerm = searchInput.value.trim().toLowerCase();
    let visibleCards = 0;

    cards.forEach((card) => {
        const matchesFilter = selectedFilter === 'all' || card.dataset.category.includes(selectedFilter);
        const searchableText = `${card.dataset.title} ${card.dataset.author} ${card.dataset.publisher}`.toLowerCase();
        const matchesSearch = !searchTerm || searchableText.includes(searchTerm);
        const visible = matchesFilter && matchesSearch;
        card.hidden = !visible;
        if (visible) visibleCards += 1;
    });

    emptyState.hidden = visibleCards > 0;
}

filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        selectedFilter = button.dataset.filter;
        filterButtons.forEach((item) => item.classList.toggle('active', item === button));
        renderBooks();
    });
});

searchInput?.addEventListener('input', renderBooks);
document.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        if (!searchInput) return;

        event.preventDefault();
        searchInput.focus();
    }
});

const profileTrigger = document.querySelector('#profile-trigger');
const profileMenu = document.querySelector('#profile-menu');
const closeProfileMenu = () => {
    if (!profileTrigger || !profileMenu) return;

    profileMenu.hidden = true;
    profileTrigger.setAttribute('aria-expanded', 'false');
};

const notificationPopover = document.querySelector('#notification-popover');
const notificationTrigger = document.querySelector('#notification-trigger');
const closeNotificationPopover = () => {
    notificationPopover?.classList.remove('open');
    notificationTrigger?.setAttribute('aria-expanded', 'false');
    notificationTrigger?.setAttribute('aria-label', 'Show notifications');
};

profileTrigger?.addEventListener('click', (event) => {
    event.stopPropagation();
    closeNotificationPopover();
    if (!profileMenu) return;

    const isOpen = !profileMenu.hidden;
    profileMenu.hidden = isOpen;
    profileTrigger.setAttribute('aria-expanded', String(!isOpen));
});
profileMenu?.addEventListener('click', (event) => event.stopPropagation());

notificationTrigger?.addEventListener('click', (event) => {
    event.stopPropagation();
    if (!notificationPopover) return;

    const isOpen = !notificationPopover.classList.contains('open');
    closeProfileMenu();
    notificationPopover.classList.toggle('open', isOpen);
    notificationTrigger.setAttribute('aria-expanded', String(isOpen));
    notificationTrigger.setAttribute('aria-label', isOpen ? 'Hide notifications' : 'Show notifications');
});
document.addEventListener('click', (event) => {
    if (notificationPopover && !notificationPopover.contains(event.target) && event.target !== notificationTrigger) {
        closeNotificationPopover();
    }
    if (profileMenu && !profileMenu.contains(event.target) && event.target !== profileTrigger) {
        closeProfileMenu();
    }
});

document.querySelectorAll('[data-profile-link]').forEach((link) => {
    link.addEventListener('click', closeProfileMenu);
});
document.querySelector('#mark-read')?.addEventListener('click', async () => {
    try {
        await postJson('/notifications/read');
        document.querySelector('.icon-alert')?.remove();
        document.querySelector('.notification-dot')?.remove();
        const markReadButton = document.querySelector('#mark-read');
        markReadButton?.setAttribute('aria-disabled', 'true');
        if (markReadButton) markReadButton.disabled = true;
        closeNotificationPopover();
        showToast('All notifications marked as read.');
    } catch (error) {
        showToast(error.message);
    }
});

const sidebar = document.querySelector('#sidebar');
const sidebarBackdrop = document.querySelector('#sidebar-backdrop');
const mobileMenu = document.querySelector('#mobile-menu');
const setSidebarOpen = (open) => {
    sidebar?.classList.toggle('open', open);
    sidebarBackdrop?.classList.toggle('open', open);
    mobileMenu?.setAttribute('aria-expanded', String(open));
    mobileMenu?.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
};

mobileMenu?.addEventListener('click', () => setSidebarOpen(!sidebar?.classList.contains('open')));
sidebarBackdrop?.addEventListener('click', () => setSidebarOpen(false));
const navItems = [...document.querySelectorAll('.nav-item[data-nav]')];
const setActiveNav = (activeItem) => {
    navItems.forEach((item) => {
        const isActive = item === activeItem;
        item.classList.toggle('active', isActive);
        if (isActive) {
            item.setAttribute('aria-current', 'page');
        } else {
            item.removeAttribute('aria-current');
        }
    });
};
const syncHashNavigation = () => {
    const currentHash = window.location.hash || '#command-center';
    const activeItem = navItems.find((item) => item.getAttribute('href') === currentHash);
    if (activeItem) setActiveNav(activeItem);
};

window.addEventListener('hashchange', syncHashNavigation);
syncHashNavigation();
navItems.forEach((item) => {
    item.addEventListener('click', () => {
        setActiveNav(item);
        setSidebarOpen(false);
    });
});
