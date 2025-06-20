<?php
/**
 * Payment and Invoice Modals for Hostel Management System
 * Contains HTML for all modals used in the billing system
 */
?>

<!-- Payment Modal -->
<div class="modal" id="paymentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-credit-card"></i> Make Payment</h4>
                <button type="button" class="close" onclick="paymentHandler.closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" method="POST" action="billing.php?tab=bills">
                    <input type="hidden" name="action" value="make_payment">
                    <input type="hidden" name="bill_id" id="bill_id" value="">
                    
                    <div class="payment-summary">
                        <div class="payment-summary-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="payment-summary-details">
                            <p class="text-primary mb-1">Payment Summary</p>
                            <p class="text-muted">Please review your payment details below</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount to Pay (RM):</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">RM</span>
                            </div>
                            <input type="number" class="form-control" id="amount" name="amount" required step="0.01" min="0.01">
                        </div>
                        <small class="form-text text-muted">The remaining amount due is RM <span id="remainingAmount">0.00</span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="">-- Select Payment Method --</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_number">Reference Number:</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Transaction reference, if applicable">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional information about this payment"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="paymentHandler.closePaymentModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="paymentHandler.submitPayment()"><i class="fas fa-check"></i> Submit Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div class="modal" id="refundModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-undo-alt"></i> Request Refund</h4>
                <button type="button" class="close" onclick="paymentHandler.closeRefundModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="refundForm" method="POST" action="billing.php?tab=refunds">
                    <input type="hidden" name="action" value="request_refund">
                    <input type="hidden" name="payment_id" id="refund_payment_id" value="">
                    
                    <div class="refund-info">
                        <div class="refund-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="refund-text">
                            <p>Refund requests are subject to approval by the hostel administration. 
                            You will be notified once your request has been processed.</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="refund_amount">Refund Amount (RM):</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">RM</span>
                            </div>
                            <input type="number" class="form-control" id="refund_amount" name="refund_amount" required step="0.01" min="0.01">
                        </div>
                        <small class="form-text text-muted">Maximum refund amount is RM <span id="maxRefundAmount">0.00</span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="refund_reason">Reason for Refund:</label>
                        <textarea class="form-control" id="refund_reason" name="refund_reason" rows="4" required placeholder="Please explain why you are requesting a refund"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="paymentHandler.closeRefundModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="paymentHandler.submitRefund()"><i class="fas fa-paper-plane"></i> Submit Request</button>
            </div>
        </div>
    </div>
</div>

<!-- Invoice View Modal -->
<div class="modal" id="invoiceModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-file-invoice"></i> Invoice</h4>
                <button type="button" class="close" onclick="invoiceHandler.closeInvoiceModal()">&times;</button>
            </div>
            <div class="modal-body" id="invoiceContent">
                <!-- Invoice content will be dynamically inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="invoiceHandler.closeInvoiceModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="invoiceHandler.printInvoiceModal()">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
                <button type="button" class="btn btn-success" onclick="invoiceHandler.downloadInvoicePDF()">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>
</div>
