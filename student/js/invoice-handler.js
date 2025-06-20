/**
 * Invoice Handler for MMU Hostel Management System
 * Manages invoice viewing, printing and generation
 * 
 * Dependencies:
 * - pdf-generator.js
 */

class InvoiceHandler {
    constructor() {
        this.currentInvoice = null;
    }

    /**
     * View an invoice in the modal
     * @param {Object} invoice - The invoice data
     * @param {string} studentName - The student's name
     * @param {string} studentId - The student's ID
     */
    viewInvoice(invoice, studentName, studentId) {
        // Store the invoice in the property
        this.currentInvoice = invoice;
        
        const today = new Date();
        const formattedDate = today.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        const paymentDate = new Date(invoice.payment_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        const generatedDate = new Date(invoice.generated_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        
        const content = `
            <div class="invoice-container">
                <div class="invoice-details">
                    <div class="invoice-header">
                        <div>
                            <div class="invoice-title">INVOICE</div>
                            <div class="invoice-number">#${invoice.invoice_number}</div>
                        </div>
                        <div>
                            <div class="invoice-date">Date: ${generatedDate}</div>
                            <div class="invoice-status">Status: <span class="badge badge-success">Paid</span></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col">
                            <h4>Billed To:</h4>
                            <div class="billed-to">
                                <p><strong>${studentName}</strong></p>
                                <p>Student ID: ${studentId}</p>
                                <p>Multimedia University</p>
                                <p>Cyberjaya, Selangor, Malaysia</p>
                            </div>
                        </div>
                        <div class="col">
                            <h4>From:</h4>
                            <div class="billed-from">
                                <p><strong>MMU Hostel Management</strong></p>
                                <p>Multimedia University</p>
                                <p>Jalan Multimedia, 63100</p>
                                <p>Cyberjaya, Selangor, Malaysia</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="invoice-items-container">
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Semester</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>Hostel Accommodation Fee</strong><br>
                                        <small class="text-muted">Payment for student housing</small>
                                    </td>
                                    <td>${invoice.semester} ${invoice.academic_year}</td>
                                    <td class="text-right">RM ${parseFloat(invoice.amount).toFixed(2)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="invoice-summary">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="payment-info">
                                    <h4>Payment Information:</h4>
                                    <table class="payment-info-table">
                                        <tr>
                                            <td><strong>Method:</strong></td>
                                            <td>${invoice.payment_method.replace(/_/g, ' ').toUpperCase()}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Reference:</strong></td>
                                            <td>${invoice.reference_number || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date:</strong></td>
                                            <td>${paymentDate}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="invoice-totals">
                                    <div class="invoice-total-row">
                                        <div>Subtotal:</div>
                                        <div>RM ${parseFloat(invoice.amount).toFixed(2)}</div>
                                    </div>
                                    <div class="invoice-total-row">
                                        <div>Tax:</div>
                                        <div>RM 0.00</div>
                                    </div>
                                    <div class="invoice-total-row total">
                                        <div>Total:</div>
                                        <div class="invoice-total-amount">RM ${parseFloat(invoice.amount).toFixed(2)}</div>
                                    </div>
                                    <div class="invoice-total-row paid">
                                        <div>Amount Paid:</div>
                                        <div>RM ${parseFloat(invoice.amount).toFixed(2)}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="invoice-footer">
                        <p><strong>Notes:</strong></p>
                        <p>This is an official receipt of your payment. Thank you for your prompt payment.</p>
                        <p>For any inquiries, please contact the hostel office at hostel@mmu.edu.my or call +603-8312-5555.</p>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('invoiceContent').innerHTML = content;
        document.getElementById('invoiceModal').style.display = 'block';
    }

    /**
     * Close the invoice modal
     */
    closeInvoiceModal() {
        document.getElementById('invoiceModal').style.display = 'none';
    }

    /**
     * Print the current invoice from the modal
     */
    printInvoiceModal() {
        const content = document.getElementById('invoiceContent').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>Invoice</title>
                <style>
                    body { 
                        font-family: 'Segoe UI', Arial, sans-serif; 
                        margin: 0; 
                        padding: 20px;
                        color: #333;
                        line-height: 1.5;
                    }
                    .invoice-container { 
                        max-width: 800px; 
                        margin: 0 auto; 
                        background: #fff;
                        box-shadow: 0 0 10px rgba(0,0,0,0.1);
                        border-radius: 5px;
                    }
                    .invoice-details {
                        padding: 30px;
                    }
                    .invoice-header { 
                        display: flex; 
                        justify-content: space-between; 
                        margin-bottom: 30px;
                        padding-bottom: 20px;
                        border-bottom: 2px solid #4e73df;
                    }
                    .invoice-title { 
                        font-size: 28px; 
                        font-weight: bold;
                        color: #4e73df;
                        letter-spacing: 1px;
                    }
                    .invoice-number { 
                        font-size: 16px;
                        color: #666;
                        margin-top: 5px;
                    }
                    .invoice-date, .invoice-status { 
                        color: #666;
                        margin-bottom: 5px;
                    }
                    .badge {
                        display: inline-block;
                        padding: 3px 8px;
                        font-size: 12px;
                        font-weight: 600;
                        border-radius: 3px;
                        background-color: #28a745;
                        color: white;
                    }
                    .row {
                        display: flex;
                        flex-wrap: wrap;
                        margin-right: -15px;
                        margin-left: -15px;
                    }
                    .col {
                        flex: 1;
                        padding: 0 15px;
                    }
                    h4 {
                        margin-top: 20px;
                        margin-bottom: 10px;
                        font-size: 18px;
                    }
                    p {
                        margin: 5px 0;
                    }
                    .billed-to, .billed-from {
                        margin-bottom: 20px;
                    }
                    .invoice-items-container {
                        margin: 20px 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    table th, table td {
                        padding: 10px;
                        text-align: left;
                        border-bottom: 1px solid #ddd;
                    }
                    table th {
                        background-color: #f8f9fa;
                    }
                    .text-right {
                        text-align: right;
                    }
                    .text-muted {
                        color: #6c757d;
                    }
                    .payment-info-table {
                        width: auto;
                    }
                    .payment-info-table td {
                        padding: 5px 10px 5px 0;
                    }
                    .invoice-total-row {
                        display: flex;
                        justify-content: space-between;
                        padding: 5px 0;
                    }
                    .invoice-total-row.total {
                        font-weight: bold;
                        border-top: 1px solid #ddd;
                        padding-top: 10px;
                        margin-top: 5px;
                    }
                    .invoice-total-row.paid {
                        color: #28a745;
                        font-weight: bold;
                    }
                    .invoice-footer {
                        margin-top: 30px;
                        padding-top: 20px;
                        border-top: 1px solid #ddd;
                        font-size: 14px;
                    }
                </style>
            </head>
            <body>
                ${content}
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }

    /**
     * Print an invoice directly without showing the modal first
     * @param {Object} invoice - The invoice data
     * @param {string} studentName - The student's name
     * @param {string} studentId - The student's ID
     */
    printInvoice(invoice, studentName, studentId) {
        this.currentInvoice = invoice;
        this.viewInvoice(invoice, studentName, studentId);
        setTimeout(() => {
            this.printInvoiceModal();
        }, 500);
    }

    /**
     * Download the current invoice as a PDF
     * @param {string} studentName - The student's name
     * @param {string} studentId - The student's ID
     */
    downloadInvoicePDF(studentName, studentId) {
        if (!this.currentInvoice) {
            console.error("No invoice is currently being viewed");
            return;
        }
        
        // Use the PDFGenerator class to generate the PDF
        PDFGenerator.generateInvoicePDF(this.currentInvoice, studentName, studentId);
    }
}

// Create a global instance of the InvoiceHandler
const invoiceHandler = new InvoiceHandler();
