/**
 * PDF Generator for MMU Hostel Management System
 * Uses html2pdf.js to convert invoice HTML to PDF
 * 
 * Dependencies:
 * - html2pdf.bundle.min.js (must be included in the HTML before this file)
 */

class PDFGenerator {
    /**
     * Generate a PDF from an invoice element
     * @param {HTMLElement} element - The element to convert to PDF
     * @param {string} filename - The name of the file to download
     */
    static generatePDF(element, filename) {
        if (!window.html2pdf) {
            console.error("html2pdf.js is not loaded. Make sure to include it in your HTML.");
            return;
        }

        // Define the PDF options
        const options = {
            margin: [10, 10, 10, 10],
            filename: filename,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

        // Clone the element to avoid modifying the original
        const clone = element.cloneNode(true);
        
        // Add a class to the clone for PDF-specific styles
        clone.classList.add('pdf-document');
        
        // Create a temporary container
        const container = document.createElement('div');
        container.style.position = 'absolute';
        container.style.left = '-9999px';
        container.appendChild(clone);
        document.body.appendChild(container);
        
        // Generate the PDF
        html2pdf()
            .set(options)
            .from(clone)
            .save()
            .then(() => {
                // Remove the temporary container
                document.body.removeChild(container);
            })
            .catch(error => {
                console.error("Error generating PDF:", error);
                document.body.removeChild(container);
            });
    }

    /**
     * Generate an invoice PDF from a given invoice object
     * @param {Object} invoice - The invoice data object
     * @param {string} studentName - The student's name
     * @param {string} studentId - The student's ID
     */
    static generateInvoicePDF(invoice, studentName, studentId) {
        // Format dates
        const paymentDate = new Date(invoice.payment_date).toLocaleDateString('en-US', { 
            year: 'numeric', month: 'long', day: 'numeric' 
        });
        const generatedDate = new Date(invoice.generated_date).toLocaleDateString('en-US', { 
            year: 'numeric', month: 'long', day: 'numeric' 
        });
        
        // Create filename with invoice number
        const filename = `invoice-${invoice.invoice_number}.pdf`;

        // Create a temporary element for the invoice
        const invoiceElement = document.createElement('div');
        invoiceElement.innerHTML = `
            <div class="invoice-container pdf-ready">
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
        
        // Generate the PDF
        this.generatePDF(invoiceElement, filename);
    }
}
