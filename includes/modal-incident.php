<?php
/**
 * In-App UI Modal: Report Crop Damage / Spoilage Incident
 * Allows Facility Owner to log cause of crop destruction, affected crop, and damaged quantity.
 * Automatically computes 85% owner-backed compensation if the booking is insured.
 */
?>
<div id="cc-incident-modal" class="cc-modal-backdrop modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="incident-modal-title">
    <div class="cc-modal cc-modal-lg modal-dialog" role="document">
        <div class="cc-modal-header" style="background: linear-gradient(135deg, #fff1f2 0%, #ffffff 100%); border-bottom: 2px solid #fecdd3;">
            <div class="cc-modal-title-wrap">
                <div class="cc-modal-icon-badge" style="background: #fee2e2; color: #dc2626;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 id="incident-modal-title" class="cc-modal-title" style="color: #991b1b;">Report Crop Incident</h3>
                    <p class="cc-modal-subtitle">Log chamber spoilage, equipment breakdown, or crop damage</p>
                </div>
            </div>
            <button type="button" class="cc-modal-close" data-modal-incident-close aria-label="Close modal">&times;</button>
        </div>

        <form method="POST" action="<?php echo base_url('owner/report-incident.php'); ?>" id="form-report-incident" style="display: flex; flex-direction: column; flex: 1 1 auto; overflow: hidden; margin: 0;">
            <input type="hidden" name="booking_id" id="modal-incident-booking-id" value="">
            <input type="hidden" name="has_insurance" id="modal-incident-insured" value="0">
            <input type="hidden" name="total_cost" id="modal-incident-cost" value="0">

            <div class="cc-modal-scrollable-body modal-body">
                <!-- Reservation Context Card -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                        <span class="text-muted">Booking Reference:</span>
                        <strong id="modal-incident-id-text">#--</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                        <span class="text-muted">Farmer:</span>
                        <strong id="modal-incident-farmer-text">--</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                        <span class="text-muted">Facility:</span>
                        <strong id="modal-incident-facility-text">--</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Insurance Status:</span>
                        <span id="modal-incident-insurance-badge" class="badge badge-accepted">85% Insured</span>
                    </div>
                </div>

                <!-- Cause of Destruction -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="modal_damage_cause" style="font-weight: 600; font-size: 0.88rem; color: #1e293b; display: block; margin-bottom: 0.35rem;">
                        Cause of Crop Destruction / Spoilage <span style="color: #dc2626;">*</span>
                    </label>
                    <select name="damage_cause" id="modal_damage_cause" class="form-control" required>
                        <option value="">-- Choose Destruction Cause --</option>
                        <option value="Chamber Compressor Breakdown">Chamber Compressor Breakdown / Mechanical Trip</option>
                        <option value="Unscheduled Power Outage & Backup Delay">Unscheduled Power Outage & Backup Generator Delay</option>
                        <option value="Humidity Sensor Drift & Fungal Spoilage">Humidity Sensor Drift & Fungal Spoilage</option>
                        <option value="Refrigerant Gas Leak & Temperature Spike">Refrigerant Gas Leak & Temperature Spike</option>
                        <option value="Pre-harvest Latent Disease Trigger">Pre-harvest Latent Infection Accelerated by Condensation</option>
                        <option value="Chamber Exhaust Flap Stuck">Chamber Exhaust Flap Jam / Airflow Asymmetry</option>
                    </select>
                </div>

                <div class="grid-2" style="margin-bottom: 1rem;">
                    <!-- Affected Produce -->
                    <div class="form-group">
                        <label for="modal_affected_crop" style="font-weight: 600; font-size: 0.88rem; color: #1e293b; display: block; margin-bottom: 0.35rem;">
                            Affected Crop <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" name="affected_crop" id="modal_affected_crop" class="form-control" placeholder="e.g. Potato, Tomato" required>
                    </div>

                    <!-- Affected Quantity -->
                    <div class="form-group">
                        <label for="modal_affected_qty" style="font-weight: 600; font-size: 0.88rem; color: #1e293b; display: block; margin-bottom: 0.35rem;">
                            Affected Quantity (kg) <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="number" name="affected_quantity" id="modal_affected_qty" class="form-control" min="1" step="1" required>
                    </div>
                </div>

                <!-- 85% Compensation Preview Card -->
                <div id="modal-compensation-card" style="background: #ecfdf5; border: 1.5px solid #6ee7b7; border-radius: var(--radius-md); padding: 0.85rem 1rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 700; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-shield-halved"></i> Owner-Backed Guarantee (85%)
                            </div>
                            <div style="font-size: 0.85rem; color: #047857; margin-top: 2px;">
                                Compensation owed to farmer:
                            </div>
                        </div>
                        <div style="font-size: 1.35rem; font-weight: 800; color: #047857;" id="modal-calc-comp">
                            ₹0.00
                        </div>
                    </div>
                </div>

                <div id="modal-no-insurance-card" style="display: none; background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: var(--radius-md); padding: 0.85rem 1rem;">
                    <div style="font-size: 0.85rem; color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Farmer did not select insurance. No automatic compensation payout will be initiated.</span>
                    </div>
                </div>
            </div>

            <div class="cc-modal-footer modal-footer">
                <button type="button" class="btn btn-outline" data-modal-incident-close>
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-paper-plane"></i> Submit Incident &amp; Notify Farmer
                </button>
            </div>
        </form>
    </div>
</div>
