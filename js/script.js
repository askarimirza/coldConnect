/**
 * COLDCONNECT - Client-side Utilities & Dynamic Calculators
 * Pure Vanilla JavaScript - No external dependencies
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            const isOpen = navMenu.classList.contains('open');
            navToggle.setAttribute('aria-expanded', isOpen);
        });
    }

    // 2. Dynamic Live Cost Estimator
    // Formula: Total Cost = Quantity (kg) * Price per kg/day (₹) * Duration (days)
    const qtyInput = document.getElementById('calc_quantity') || document.getElementById('quantity');
    const daysInput = document.getElementById('calc_days') || document.getElementById('duration');
    const priceInput = document.getElementById('calc_price');
    const costDisplay = document.getElementById('estimated_cost_display');
    const costHiddenInput = document.getElementById('total_cost_input');
    const formulaDisplay = document.getElementById('cost_formula_display');

    function updateEstimatedCost() {
        if (!costDisplay) return;

        const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
        const days = parseInt(daysInput ? daysInput.value : 0) || 0;
        const price = parseFloat(priceInput ? priceInput.value : (costDisplay.dataset.price || 0)) || 0;

        const total = qty * price * days;

        const formatted = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 0
        }).format(total);

        costDisplay.textContent = formatted;

        if (costHiddenInput) {
            costHiddenInput.value = total.toFixed(2);
        }

        if (formulaDisplay) {
            formulaDisplay.textContent = `${qty} kg × ₹${price.toFixed(2)}/kg/day × ${days} days`;
        }
    }

    if (qtyInput) qtyInput.addEventListener('input', updateEstimatedCost);
    if (daysInput) daysInput.addEventListener('input', updateEstimatedCost);
    if (priceInput) priceInput.addEventListener('input', updateEstimatedCost);

    // Initial calculation if inputs already present
    updateEstimatedCost();

    // 3. Date Synchronization for Booking Form
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const durationInput = document.getElementById('booking_duration');

    if (startDateInput) {
        // Enforce minimum date to today
        const today = new Date().toISOString().split('T')[0];
        startDateInput.min = today;

        if (!startDateInput.value) {
            startDateInput.value = today;
        }

        // When start date or duration changes, update end date
        function syncEndDate() {
            if (!startDateInput.value) return;
            const days = parseInt(durationInput ? durationInput.value : 15) || 1;
            const start = new Date(startDateInput.value);
            const end = new Date(start);
            end.setDate(start.getDate() + days);
            if (endDateInput) {
                endDateInput.value = end.toISOString().split('T')[0];
            }
            updateEstimatedCost();
        }

        if (durationInput) {
            durationInput.addEventListener('input', syncEndDate);
        }
        startDateInput.addEventListener('change', syncEndDate);

        // When end date changes, compute duration
        if (endDateInput) {
            endDateInput.min = today;
            endDateInput.addEventListener('change', () => {
                if (startDateInput.value && endDateInput.value) {
                    const s = new Date(startDateInput.value);
                    const e = new Date(endDateInput.value);
                    const diffTime = e - s;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays > 0 && durationInput) {
                        durationInput.value = diffDays;
                        updateEstimatedCost();
                    }
                }
            });
        }

        // Run sync on load if duration is set
        syncEndDate();
    }

    // 4. Judging Demo Quick-Fill Helper
    const demoFillBtn = document.getElementById('demo-fill-btn');
    if (demoFillBtn) {
        demoFillBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const cropSelect = document.getElementById('crop');
            const qtyField = document.getElementById('quantity');
            const durField = document.getElementById('duration');
            const locField = document.getElementById('location');

            if (cropSelect) cropSelect.value = 'Tomato';
            if (qtyField) qtyField.value = '500';
            if (durField) durField.value = '15';
            if (locField) locField.value = 'Ahmedabad';

            updateEstimatedCost();
        });
    }

    // 5. In-App UI Rejection Popup Modal Controller
    const rejectModal = document.getElementById('cc-reject-modal');
    if (rejectModal) {
        const idInput = document.getElementById('modal-reject-booking-id');
        const idTitle = document.getElementById('modal-reject-id-title');
        const farmerSpan = document.getElementById('modal-reject-farmer');
        const cropSpan = document.getElementById('modal-reject-crop');
        const qtySpan = document.getElementById('modal-reject-qty');
        const facilitySpan = document.getElementById('modal-reject-facility');

        function openRejectModal(data) {
            if (idInput) idInput.value = data.id || '';
            if (idTitle) idTitle.textContent = data.id || '--';
            if (farmerSpan) farmerSpan.textContent = data.farmer || '--';
            if (cropSpan) cropSpan.textContent = data.crop || '--';
            if (qtySpan) qtySpan.textContent = data.qty || '';
            if (facilitySpan) facilitySpan.textContent = data.facility || '--';

            rejectModal.classList.add('active');
            rejectModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeRejectModal() {
            rejectModal.classList.remove('active');
            rejectModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        // Delegate click for trigger buttons and dismiss controls
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-reject-modal');
            if (btn) {
                e.preventDefault();
                openRejectModal({
                    id: btn.dataset.id,
                    farmer: btn.dataset.farmer,
                    crop: btn.dataset.crop,
                    qty: btn.dataset.qty,
                    facility: btn.dataset.facility
                });
            }

            if (e.target.closest('[data-modal-close]')) {
                e.preventDefault();
                closeRejectModal();
            }

            if (e.target === rejectModal) {
                closeRejectModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && rejectModal.classList.contains('active')) {
                closeRejectModal();
            }
        });
    }

    // 6. Notification Bar / Bell Dropdown Controller
    const notifBtn = document.getElementById('notif-bell-btn');
    const notifDropdown = document.getElementById('notif-dropdown');
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
            const isOpen = notifDropdown.classList.contains('active');
            notifDropdown.setAttribute('aria-hidden', !isOpen);
        });

        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && e.target !== notifBtn) {
                notifDropdown.classList.remove('active');
                notifDropdown.setAttribute('aria-hidden', 'true');
            }
        });
    }

    // 7. Incident Modal Controller & 85% Compensation Dynamic Preview
    const incidentModal = document.getElementById('cc-incident-modal');
    if (incidentModal) {
        const idInput = document.getElementById('modal-incident-booking-id');
        const idText = document.getElementById('modal-incident-id-text');
        const farmerText = document.getElementById('modal-incident-farmer-text');
        const facilityText = document.getElementById('modal-incident-facility-text');
        const insBadge = document.getElementById('modal-incident-insurance-badge');
        const cropInput = document.getElementById('modal_affected_crop');
        const qtyInput = document.getElementById('modal_affected_qty');
        const compCard = document.getElementById('modal-compensation-card');
        const noInsCard = document.getElementById('modal-no-insurance-card');
        const compDisplay = document.getElementById('modal-calc-comp');
        const insuredInput = document.getElementById('modal-incident-insured');
        const costInput = document.getElementById('modal-incident-cost');

        let currentTotalQty = 1;
        let currentTotalCost = 0;
        let isInsured = true;

        function recalcCompensation() {
            if (!isInsured) {
                if (compCard) compCard.style.display = 'none';
                if (noInsCard) noInsCard.style.display = 'block';
                return;
            }
            if (compCard) compCard.style.display = 'block';
            if (noInsCard) noInsCard.style.display = 'none';

            const affQty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const ratio = currentTotalQty > 0 ? Math.min(1.0, affQty / currentTotalQty) : 1.0;
            const comp = currentTotalCost * ratio * 0.85;

            if (compDisplay) {
                compDisplay.textContent = new Intl.NumberFormat('en-IN', {
                    style: 'currency',
                    currency: 'INR',
                    maximumFractionDigits: 2
                }).format(comp);
            }
        }

        if (qtyInput) {
            qtyInput.addEventListener('input', recalcCompensation);
        }

        function openIncidentModal(data) {
            if (idInput) idInput.value = data.id || '';
            if (idText) idText.textContent = '#' + (data.id || '--');
            if (farmerText) farmerText.textContent = data.farmer || '--';
            if (facilityText) facilityText.textContent = data.facility || '--';
            if (cropInput) cropInput.value = data.crop || '';
            if (qtyInput) {
                qtyInput.value = data.qty || '';
                qtyInput.max = data.qty || '';
            }
            currentTotalQty = parseFloat(data.qty) || 1;
            currentTotalCost = parseFloat(data.cost) || 0;
            isInsured = (parseInt(data.insured, 10) === 1);

            if (insuredInput) insuredInput.value = isInsured ? '1' : '0';
            if (costInput) costInput.value = currentTotalCost;

            if (insBadge) {
                if (isInsured) {
                    insBadge.className = 'badge badge-accepted';
                    insBadge.innerHTML = '<i class="fa-solid fa-shield-halved"></i> 85% Insured';
                } else {
                    insBadge.className = 'badge';
                    insBadge.style.background = '#f1f5f9';
                    insBadge.style.color = '#64748b';
                    insBadge.textContent = 'Standard (No Insurance)';
                }
            }

            recalcCompensation();
            incidentModal.classList.add('active');
            incidentModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            setTimeout(() => {
                const causeSelect = document.getElementById('modal_damage_cause');
                if (causeSelect) causeSelect.focus();
            }, 100);
        }

        function closeIncidentModal() {
            incidentModal.classList.remove('active');
            incidentModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-incident-modal');
            if (btn) {
                e.preventDefault();
                openIncidentModal({
                    id: btn.dataset.id,
                    farmer: btn.dataset.farmer,
                    facility: btn.dataset.facility,
                    crop: btn.dataset.crop,
                    qty: btn.dataset.qty,
                    cost: btn.dataset.cost,
                    insured: btn.dataset.insured
                });
                return;
            }

            if (e.target.closest('[data-modal-incident-close]')) {
                e.preventDefault();
                closeIncidentModal();
                return;
            }

            if (e.target === incidentModal) {
                closeIncidentModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && incidentModal.classList.contains('active')) {
                closeIncidentModal();
            }
        });
    }

    // 8. View Incident Report Modal Controller (Pop-up Inspector)
    const viewIncidentModal = document.getElementById('cc-view-incident-modal');
    if (viewIncidentModal) {
        const badgeIdEl     = document.getElementById('view-incident-badge-id');
        const facilityEl    = document.getElementById('view-inc-facility');
        const facilityLocEl = document.getElementById('view-inc-facility-loc');
        const farmerEl      = document.getElementById('view-inc-farmer');
        const farmerPhoneEl = document.getElementById('view-inc-farmer-phone');
        const datesEl       = document.getElementById('view-inc-dates');
        const durationEl    = document.getElementById('view-inc-duration');
        const insBadgeEl    = document.getElementById('view-inc-insurance-badge');
        const statusTagEl   = document.getElementById('view-inc-status-tag');
        const causeEl       = document.getElementById('view-inc-cause');
        const cropEl        = document.getElementById('view-inc-crop');
        const damagedQtyEl  = document.getElementById('view-inc-damaged-qty');
        const totalQtyEl    = document.getElementById('view-inc-total-qty');
        const lossPctEl     = document.getElementById('view-inc-loss-pct');
        const progressFillEl= document.getElementById('view-inc-progress-fill');
        const resTotalEl    = document.getElementById('view-inc-res-total');
        const compAmountEl  = document.getElementById('view-inc-comp-amount');
        const compPillEl    = document.getElementById('view-inc-comp-pill');
        const settlementBox = document.getElementById('view-inc-settlement-box');
        const uninsuredBan  = document.getElementById('view-inc-uninsured-banner');

        const currencyFmt = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 2
        });

        function openViewIncidentModal(data) {
            if (!data) return;

            if (badgeIdEl) badgeIdEl.textContent = '#INC-BKG-' + (data.id || '00');
            if (facilityEl) facilityEl.textContent = data.facility || 'Storage Facility';
            if (facilityLocEl) facilityLocEl.textContent = data.facilityLocation ? 'Location: ' + data.facilityLocation : '';
            if (farmerEl) farmerEl.textContent = data.farmer || 'Farmer';
            if (farmerPhoneEl) farmerPhoneEl.textContent = data.farmerPhone ? 'Phone: ' + data.farmerPhone : '';
            if (datesEl) datesEl.textContent = (data.startDate && data.endDate) ? `${data.startDate} to ${data.endDate}` : 'Active Period';
            if (durationEl) durationEl.textContent = `Reservation #${data.id || '--'}`;
            if (statusTagEl) statusTagEl.textContent = data.status || 'Reported';
            if (causeEl) causeEl.textContent = data.cause || 'Unscheduled equipment interruption';
            if (cropEl) cropEl.textContent = data.affectedCrop || data.crop || 'Agricultural Produce';

            const damagedQty = parseFloat(data.affectedQty) || 0;
            const totalQty = parseFloat(data.qty) || damagedQty || 1;
            if (damagedQtyEl) damagedQtyEl.textContent = damagedQty.toLocaleString('en-IN') + ' kg damaged';
            if (totalQtyEl) totalQtyEl.textContent = totalQty.toLocaleString('en-IN') + ' kg booked';

            // Severity progress bar
            const lossPct = totalQty > 0 ? Math.min(100, Math.round((damagedQty / totalQty) * 100)) : 100;
            if (lossPctEl) lossPctEl.textContent = `${lossPct}% Crop Loss Ratio`;
            if (progressFillEl) progressFillEl.style.width = `${lossPct}%`;

            const totalCost = parseFloat(data.cost) || 0;
            if (resTotalEl) resTotalEl.textContent = currencyFmt.format(totalCost);

            const isInsured = (parseInt(data.insured, 10) === 1);
            const compAmount = parseFloat(data.compensation) || 0;

            if (isInsured) {
                if (insBadgeEl) insBadgeEl.innerHTML = '<span class="badge badge-accepted"><i class="fa-solid fa-shield-halved"></i> 85% Protected</span>';
                if (compPillEl) {
                    compPillEl.className = 'badge badge-accepted';
                    compPillEl.textContent = '85% Owner Guaranteed';
                }
                if (settlementBox) settlementBox.classList.remove('uninsured');
                if (uninsuredBan) uninsuredBan.style.display = 'none';
                if (compAmountEl) compAmountEl.textContent = currencyFmt.format(compAmount > 0 ? compAmount : (totalCost * 0.85));
            } else {
                if (insBadgeEl) insBadgeEl.innerHTML = '<span class="badge" style="background:#f1f5f9;color:#64748b;"><i class="fa-solid fa-shield"></i> Uninsured Standard</span>';
                if (compPillEl) {
                    compPillEl.className = 'badge badge-rejected';
                    compPillEl.textContent = 'No Claim Coverage';
                }
                if (settlementBox) settlementBox.classList.add('uninsured');
                if (uninsuredBan) uninsuredBan.style.display = 'block';
                if (compAmountEl) compAmountEl.textContent = '₹0.00';
            }

            viewIncidentModal.classList.add('active');
            viewIncidentModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeViewIncidentModal() {
            viewIncidentModal.classList.remove('active');
            viewIncidentModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-view-incident');
            if (btn) {
                e.preventDefault();
                openViewIncidentModal({
                    id: btn.dataset.id,
                    farmer: btn.dataset.farmer,
                    farmerPhone: btn.dataset.farmerPhone,
                    facility: btn.dataset.facility,
                    facilityLocation: btn.dataset.facilityLocation,
                    startDate: btn.dataset.startDate,
                    endDate: btn.dataset.endDate,
                    crop: btn.dataset.crop,
                    qty: btn.dataset.qty,
                    affectedCrop: btn.dataset.affectedCrop,
                    affectedQty: btn.dataset.affectedQty,
                    cause: btn.dataset.cause,
                    cost: btn.dataset.cost,
                    insured: btn.dataset.insured,
                    compensation: btn.dataset.compensation,
                    status: btn.dataset.status
                });
                return;
            }

            if (e.target.closest('[data-modal-view-incident-close]')) {
                e.preventDefault();
                closeViewIncidentModal();
                return;
            }

            if (e.target === viewIncidentModal) {
                closeViewIncidentModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && viewIncidentModal.classList.contains('active')) {
                closeViewIncidentModal();
            }
        });

        // Auto-open incident report popup if query param view_incident is in URL
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const viewIncId = urlParams.get('view_incident');
            if (viewIncId) {
                const targetBtn = document.querySelector(`.btn-view-incident[data-id="${viewIncId}"]`);
                if (targetBtn) {
                    setTimeout(() => targetBtn.click(), 250);
                }
            }
        } catch (err) {
            console.warn('ColdConnect view_incident parser error:', err);
        }
    }
});


