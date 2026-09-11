<?php
/**
 * COLDCONNECT - Reusable Reject Booking Modal Dialog
 * Clean, accessible in-app popup modal replacing default browser confirm().
 */
?>
<!-- ColdConnect Rejection Confirmation Modal -->
<div id="cc-reject-modal" class="cc-modal-backdrop" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cc-reject-modal-title">
    <div class="cc-modal" role="document">
        <!-- Header -->
        <div class="cc-modal-header">
            <div class="cc-modal-title-wrap">
                <div class="cc-modal-icon-badge">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 id="cc-reject-modal-title" class="cc-modal-title">Reject Booking Request</h3>
                    <p class="cc-modal-subtitle">Reservation #<span id="modal-reject-id-title">--</span></p>
                </div>
            </div>
            <button type="button" class="cc-modal-close" data-modal-close aria-label="Close modal">&times;</button>
        </div>

        <!-- Body -->
        <div class="cc-modal-body">
            <p class="cc-modal-warning-text">
                Are you sure you want to decline this booking request? The farmer will be notified of the cancellation and the reserved space will remain open.
            </p>

            <div class="cc-modal-summary-card">
                <div class="cc-modal-summary-row">
                    <span class="cc-modal-label">Farmer</span>
                    <strong id="modal-reject-farmer" class="cc-modal-val">--</strong>
                </div>
                <div class="cc-modal-summary-row">
                    <span class="cc-modal-label">Crop &amp; Quantity</span>
                    <strong class="cc-modal-val">
                        <span id="modal-reject-crop" class="crop-tag highlight">--</span>
                        <span id="modal-reject-qty" style="margin-left: 0.35rem;">--</span>
                    </strong>
                </div>
                <div class="cc-modal-summary-row">
                    <span class="cc-modal-label">Target Facility</span>
                    <strong id="modal-reject-facility" class="cc-modal-val">--</strong>
                </div>
            </div>
        </div>

        <!-- Footer / Action Form -->
        <div class="cc-modal-footer">
            <form method="POST" action="<?php echo base_url('owner/update-booking.php'); ?>" id="cc-reject-form" style="display: flex; gap: 0.75rem; width: 100%; justify-content: flex-end;">
                <input type="hidden" name="booking_id" id="modal-reject-booking-id" value="">
                <input type="hidden" name="action" value="reject">
                
                <button type="button" class="btn btn-outline" data-modal-close>
                    Keep Booking
                </button>
                <button type="submit" class="btn btn-danger" id="cc-confirm-reject-btn">
                    <i class="fa-solid fa-circle-xmark"></i> Confirm Rejection
                </button>
            </form>
        </div>
    </div>
</div>
