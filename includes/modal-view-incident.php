<?php
/**
 * COLDCONNECT - View Incident Report Modal Dialog
 * In-app interactive pop-up presenting the official incident & spoilage claim report.
 * Provides transparent breakdown of cause, damaged produce, and 85% owner-backed payout.
 */
?>
<div id="cc-view-incident-modal" class="cc-modal-backdrop modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="view-incident-title">
    <div class="cc-modal cc-modal-xl modal-dialog incident-report-doc" role="document">
        <!-- Header -->
        <div class="cc-modal-header" style="background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 60%, #ffffff 100%); border-bottom: 2px solid #fecdd3;">
            <div class="cc-modal-title-wrap">
                <div class="cc-modal-icon-badge" style="background: #fee2e2; color: #dc2626;">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <h3 id="view-incident-title" class="cc-modal-title" style="color: #991b1b; font-size: 1.2rem;">
                            Crop Damage Incident Report
                        </h3>
                        <span id="view-incident-badge-id" class="report-watermark-seal">
                            #INC-BKG-00
                        </span>
                    </div>
                    <p class="cc-modal-subtitle" style="color: #64748b;">
                        Official inspection record &bull; ColdConnect Spoilage Guarantee
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <button type="button" class="btn btn-outline btn-sm no-print" onclick="window.print()" title="Print or save as PDF" style="font-size: 0.78rem; padding: 0.35rem 0.65rem;">
                    <i class="fa-solid fa-print"></i> Print
                </button>
                <button type="button" class="cc-modal-close modal-close no-print" data-modal-view-incident-close aria-label="Close modal">&times;</button>
            </div>
        </div>

        <!-- Scrollable Report Body -->
        <div class="cc-modal-scrollable-body modal-body">
            <!-- Top Reservation Metadata Grid -->
            <div class="report-grid-meta">
                <div class="report-meta-item">
                    <span class="meta-label"><i class="fa-solid fa-warehouse"></i> Storage Facility</span>
                    <span id="view-inc-facility" class="meta-value">--</span>
                    <span id="view-inc-facility-loc" style="font-size: 0.78rem; color: #64748b;">--</span>
                </div>
                <div class="report-meta-item">
                    <span class="meta-label"><i class="fa-solid fa-user"></i> Farmer</span>
                    <span id="view-inc-farmer" class="meta-value">--</span>
                    <span id="view-inc-farmer-phone" style="font-size: 0.78rem; color: #64748b;">--</span>
                </div>
                <div class="report-meta-item">
                    <span class="meta-label"><i class="fa-solid fa-calendar-days"></i> Booking Period</span>
                    <span id="view-inc-dates" class="meta-value">--</span>
                    <span id="view-inc-duration" style="font-size: 0.78rem; color: #64748b;">--</span>
                </div>
                <div class="report-meta-item">
                    <span class="meta-label"><i class="fa-solid fa-shield-halved"></i> Insurance Status</span>
                    <span id="view-inc-insurance-badge" style="display: inline-block; margin-top: 2px;">
                        <span class="badge badge-accepted">85% Insured</span>
                    </span>
                </div>
            </div>

            <!-- Spoilage Incident Particulars Card -->
            <div class="report-card-section">
                <div class="report-card-section-header">
                    <span><i class="fa-solid fa-triangle-exclamation" style="color: #dc2626; margin-right: 0.35rem;"></i> Damage Particulars</span>
                    <span id="view-inc-status-tag" class="badge badge-rejected" style="font-size: 0.72rem;">Reported</span>
                </div>
                <div class="report-card-section-body">
                    <div style="margin-bottom: 0.85rem;">
                        <span style="font-size: 0.78rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Official Cause of Spoilage / Destruction</span>
                        <div id="view-inc-cause" style="font-size: 0.98rem; font-weight: 700; color: #991b1b; margin-top: 0.2rem;">
                            --
                        </div>
                    </div>

                    <div class="grid-2" style="gap: 1rem; margin-top: 0.75rem;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 0.75rem;">
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Affected Crop</span>
                            <div id="view-inc-crop" style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                --
                            </div>
                        </div>

                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 0.75rem;">
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Damaged vs Total Quantity</span>
                            <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                <span id="view-inc-damaged-qty" style="color: #dc2626;">0 kg</span>
                                <span style="font-size: 0.85rem; color: #64748b; font-weight: 400;">/ <span id="view-inc-total-qty">0 kg</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Spoilage Severity Bar -->
                    <div class="damage-progress-container">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 0.25rem;">
                            <span>Loss Ratio Severity</span>
                            <span id="view-inc-loss-pct" style="color: #dc2626; font-weight: 700;">0%</span>
                        </div>
                        <div class="damage-progress-bar">
                            <div id="view-inc-progress-fill" class="damage-progress-fill" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Settlement & 85% Compensation Card -->
            <div class="report-card-section" style="border-color: #a7f3d0; margin-bottom: 0;">
                <div class="report-card-section-header" style="background: #f0fdf4; border-color: #a7f3d0; color: #065f46;">
                    <span><i class="fa-solid fa-shield-halved" style="color: #059669; margin-right: 0.35rem;"></i> Financial Compensation Breakdown</span>
                    <span id="view-inc-comp-pill" class="badge badge-accepted" style="font-size: 0.72rem;">85% Guaranteed</span>
                </div>
                <div class="report-card-section-body" style="background: #fafdfb;">
                    <div class="compensation-settlement-box" id="view-inc-settlement-box">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 700; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px;">
                                Owner-Guaranteed Compensation Payout
                            </div>
                            <div style="font-size: 0.85rem; color: #047857; margin-top: 2px;">
                                Calculated at <strong>85%</strong> of damaged crop reservation value:
                            </div>
                            <div style="font-size: 0.78rem; color: #64748b; margin-top: 4px;">
                                Reservation Total: <strong id="view-inc-res-total">₹0.00</strong>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div id="view-inc-comp-amount" class="settlement-amount-highlight">
                                ₹0.00
                            </div>
                            <div style="font-size: 0.72rem; color: #059669; font-weight: 600;">
                                <i class="fa-solid fa-circle-check"></i> Disbursed to Farmer
                            </div>
                        </div>
                    </div>

                    <!-- Uninsured Notice Banner (hidden by default) -->
                    <div id="view-inc-uninsured-banner" style="display: none; background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-top: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.6rem; color: #991b1b; font-size: 0.85rem;">
                            <i class="fa-solid fa-circle-xmark" style="font-size: 1.1rem;"></i>
                            <div>
                                <strong>Standard Reservation (No Spoilage Insurance):</strong>
                                <div style="font-size: 0.78rem; color: #b91c1c; margin-top: 2px;">
                                    This reservation was booked under standard storage without the 85% spoilage guarantee option. No owner compensation has been authorized.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Protocol Watermark Footnote -->
                    <div class="report-security-badge">
                        <i class="fa-solid fa-certificate" style="color: #059669; font-size: 1.1rem;"></i>
                        <div>
                            <strong>ColdConnect Agricultural Spoilage Protection Protocol</strong> &bull;
                            <span>Record stored permanently in ledger. Transparently viewable by facility owner and farmer.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="cc-modal-footer modal-footer no-print">
            <button type="button" class="btn btn-outline" data-modal-view-incident-close>
                Close Report
            </button>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Document
            </button>
        </div>
    </div>
</div>
