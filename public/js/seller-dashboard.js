(function () {
    'use strict';

    const configEl = document.getElementById('seller-dashboard-config');
    if (!configEl) {
        return;
    }

    const config = JSON.parse(configEl.textContent);
    const token = config.token;
    const t = config.translations || {};

    const drawer = document.getElementById('inquiries-drawer');
    const drawerBackdrop = document.getElementById('inquiries-drawer-backdrop');
    const drawerContent = document.getElementById('inquiries-drawer-content');
    const drawerSubtitle = document.getElementById('inquiries-drawer-subtitle');

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function showSnackbar(message, type) {
        if (window.showSnackbar) {
            window.showSnackbar(message, type);
        }
    }

    function openDrawer() {
        if (!drawer || !drawerBackdrop) {
            return;
        }
        drawer.classList.add('is-open');
        drawerBackdrop.classList.add('is-visible');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (!drawer || !drawerBackdrop) {
            return;
        }
        drawer.classList.remove('is-open');
        drawerBackdrop.classList.remove('is-visible');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function renderLoadingState() {
        if (!drawerContent || !drawerSubtitle) {
            return;
        }
        drawerSubtitle.textContent = t.loadingInquiries || 'Indlæser henvendelser...';
        drawerContent.innerHTML = `
            <div class="panel-table-empty">
                <svg class="animate-spin" style="width:2rem;height:2rem;color:var(--primary)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p>${t.loadingInquiries || 'Indlæser henvendelser...'}</p>
            </div>
        `;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderInquiries(inquiries) {
        if (!drawerContent || !drawerSubtitle) {
            return;
        }

        const countLabel = inquiries.length === 1
            ? (t.inquiryCount || ':count henvendelse').replace(':count', inquiries.length)
            : (t.inquiriesCount || ':count henvendelser').replace(':count', inquiries.length);

        drawerSubtitle.textContent = `${inquiries.length} ${countLabel} ${t.forThisVehicle || 'til dette køretøj'}`;

        if (inquiries.length === 0) {
            drawerContent.innerHTML = `
                <div class="panel-table-empty">
                    <p class="panel-table-empty__title">${t.noInquiriesYet || 'Ingen henvendelser endnu'}</p>
                    <p>${t.noInquiriesDescription || 'Dette køretøj har ikke modtaget nogen henvendelser.'}</p>
                </div>
            `;
            return;
        }

        let html = '';
        inquiries.forEach((enquiry) => {
            const date = new Date(enquiry.created_at);
            const formattedDate = date.toLocaleDateString(undefined, {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            });
            const name = escapeHtml(enquiry.name || t.anonymous || 'Anonym');
            const email = enquiry.email ? escapeHtml(enquiry.email) : '';
            const phone = enquiry.phone ? escapeHtml(enquiry.phone) : '';
            const subject = enquiry.subject ? `<p style="font-weight:600;margin:0 0 0.5rem;font-size:0.8125rem">${escapeHtml(enquiry.subject)}</p>` : '';
            const message = escapeHtml(enquiry.message || t.noMessageProvided || 'Ingen besked angivet');
            const typeBadge = enquiry.type
                ? `<span class="panel-status-chip panel-status-chip--info" style="margin-top:0.375rem">${escapeHtml(enquiry.type)}</span>`
                : '';

            const contactActions = [];
            if (enquiry.email) {
                contactActions.push(`<a href="mailto:${encodeURIComponent(enquiry.email)}" class="panel-btn panel-btn--outline panel-btn--sm">${t.sendEmail || 'Send e-mail'}</a>`);
            }
            if (enquiry.phone) {
                contactActions.push(`<a href="tel:${encodeURIComponent(enquiry.phone)}" class="panel-btn panel-btn--outline panel-btn--sm">${t.call || 'Ring op'}</a>`);
            }

            html += `
                <article class="panel-inquiry-card">
                    <div class="panel-inquiry-card__header">
                        <div>
                            <p class="panel-inquiry-card__name">${name}</p>
                            ${email ? `<p class="panel-inquiry-card__meta">${email}</p>` : ''}
                            ${phone ? `<p class="panel-inquiry-card__meta">${phone}</p>` : ''}
                        </div>
                        <div style="text-align:right">
                            <span class="panel-inquiry-card__date">${formattedDate}</span>
                            ${typeBadge}
                        </div>
                    </div>
                    ${subject}
                    <p class="panel-inquiry-card__message">${message}</p>
                    ${contactActions.length ? `<div class="panel-inquiry-card__actions">${contactActions.join('')}</div>` : ''}
                </article>
            `;
        });

        drawerContent.innerHTML = html;
    }

    window.toggleInquiries = function (vehicleId) {
        openDrawer();
        renderLoadingState();

        fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}/inquiries`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === 'success' && data.inquiries) {
                    renderInquiries(data.inquiries);
                } else {
                    drawerContent.innerHTML = `
                        <div class="panel-table-empty">
                            <p class="panel-table-empty__title" style="color:var(--destructive)">${t.failedToLoadInquiries || 'Kunne ikke indlæse henvendelser. Prøv venligst igen.'}</p>
                        </div>
                    `;
                    drawerSubtitle.textContent = t.errorLoadingInquiries || 'Fejl ved indlæsning af henvendelser';
                }
            })
            .catch(() => {
                drawerContent.innerHTML = `
                    <div class="panel-table-empty">
                        <p class="panel-table-empty__title" style="color:var(--destructive)">${t.errorOccurredLoading || 'Der opstod en fejl under indlæsning af henvendelser. Prøv venligst igen.'}</p>
                    </div>
                `;
                drawerSubtitle.textContent = t.errorLoadingInquiries || 'Fejl ved indlæsning af henvendelser';
            });
    };

    window.closeInquiriesDrawer = closeDrawer;

    window.unpublishVehicle = function (vehicleId) {
        if (!confirm(t.confirmUnpublish || 'Er du sikker på, at du vil afpublicere dette køretøj? Det vil blive fjernet fra offentlige annoncer.')) {
            return;
        }

        fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}/unpublish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === 'success') {
                    showSnackbar(t.vehicleUnpublishedSuccessfully || 'Køretøj afpubliceret med succes', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showSnackbar(data.message || t.failedToUnpublish || 'Kunne ikke afpublicere køretøj', 'error');
                }
            })
            .catch(() => {
                showSnackbar(t.genericError || 'Der opstod en fejl. Prøv venligst igen.', 'error');
            });
    };

    window.updateStatus = function (vehicleId, statusId) {
        fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ list_status_id: statusId }),
            credentials: 'same-origin',
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === 'success') {
                    showSnackbar(t.vehicleStatusUpdated || 'Køretøjsstatus opdateret med succes', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showSnackbar(data.message || t.failedToUpdateStatus || 'Kunne ikke opdatere køretøjsstatus', 'error');
                }
            })
            .catch(() => {
                showSnackbar(t.genericError || 'Der opstod en fejl. Prøv venligst igen.', 'error');
            });
    };

    window.deleteVehicle = function (vehicleId) {
        if (!confirm(t.confirmDelete || 'Er du sikker på, at du vil slette dette køretøj? Denne handling kan ikke fortrydes.')) {
            return;
        }

        fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === 'success') {
                    showSnackbar(t.vehicleDeletedSuccessfully || 'Køretøj slettet med succes', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showSnackbar(data.message || t.failedToDelete || 'Kunne ikke slette køretøj', 'error');
                }
            })
            .catch(() => {
                showSnackbar(t.genericError || 'Der opstod en fejl. Prøv venligst igen.', 'error');
            });
    };

    function initDropdowns() {
        document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
            const trigger = dropdown.querySelector('.panel-dropdown__trigger');
            if (!trigger) {
                return;
            }

            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = dropdown.classList.contains('is-open');

                document.querySelectorAll('[data-dropdown].is-open').forEach((openDropdown) => {
                    if (openDropdown !== dropdown) {
                        openDropdown.classList.remove('is-open');
                        const openTrigger = openDropdown.querySelector('.panel-dropdown__trigger');
                        if (openTrigger) {
                            openTrigger.setAttribute('aria-expanded', 'false');
                        }
                    }
                });

                dropdown.classList.toggle('is-open', !isOpen);
                trigger.setAttribute('aria-expanded', String(!isOpen));
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('[data-dropdown].is-open').forEach((dropdown) => {
                dropdown.classList.remove('is-open');
                const trigger = dropdown.querySelector('.panel-dropdown__trigger');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    function initSearch() {
        const searchInput = document.getElementById('seller-vehicle-search');
        if (!searchInput) {
            return;
        }

        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            document.querySelectorAll('[data-vehicle-row]').forEach((row) => {
                const haystack = (row.getAttribute('data-search') || '').toLowerCase();
                row.style.display = !query || haystack.includes(query) ? '' : 'none';
            });
        });
    }

    function initDrawer() {
        if (drawerBackdrop) {
            drawerBackdrop.addEventListener('click', closeDrawer);
        }

        const closeButton = document.getElementById('inquiries-drawer-close');
        if (closeButton) {
            closeButton.addEventListener('click', closeDrawer);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDrawer();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initDropdowns();
        initSearch();
        initDrawer();
    });
})();
