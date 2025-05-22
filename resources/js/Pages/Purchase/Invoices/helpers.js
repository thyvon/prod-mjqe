export const formatCurrency = (value, currency) => {
  const symbol = currency === 1 ? '$' : '៛';
  const num = isNaN(parseFloat(value)) ? 0 : parseFloat(value);
  return `${symbol} ${num.toFixed(4)}`;
};

export const formatDate = (date) => {
  if (!date) return '';
  const formattedDate = new Date(date);
  return formattedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
};

export const getTransactionType = (type) => {
  switch (type) {
    case 1: return 'Petty Cash';
    case 2: return 'Credit';
    case 3: return 'Advance';
    default: return 'Unknown';
  }
};

export const getPaymentType = (type) => {
  switch (type) {
    case 1: return 'Final';
    case 2: return 'Deposit';
    default: return 'Unknown';
  }
};

export const getPaymentTerm = (term) => {
  switch (term) {
    case 1: return 'Credit 1 week';
    case 2: return 'Credit 2 weeks';
    case 3: return 'Credit 1 month';
    case 4: return 'Non-Credit';
    default: return 'Unknown';
  }
}

export const getFileThumbnail = (fileUrl) => {
  if (!fileUrl) {
    return '/images/default-file-icon.png'; // Fallback for empty URLs
  }

  // Remove query parameters from the URL
  const cleanUrl = fileUrl.split('?')[0];

  // Extract the file extension
  const extension = cleanUrl.split('.').pop().toLowerCase();
  const thumbnailMap = {
    pdf: '/images/thumbnails-pdf.png',
    doc: '/images/thumbnails-doc.png',
    docx: '/images/thumbnails-doc.png',
    xls: '/images/thumbnails-xls.png',
    xlsx: '/images/thumbnails-xls.png',
    ppt: '/images/thumbnails-ppt.png',
    pptx: '/images/thumbnails-ppt.png',
    jpg: fileUrl, // Use the file URL directly for images
    jpeg: fileUrl,
    png: fileUrl,
    gif: fileUrl,
    bmp: fileUrl,
    svg: fileUrl,
  };

  // Check if the extension exists in the map
  const thumbnailUrl = thumbnailMap[extension] || '/images/default-file-icon.png';

  // Append a unique query parameter to force refresh
  return `${thumbnailUrl}?t=${new Date().getTime()}`;
};

export const openPdfViewer = (url) => {
  if (!url) {
    toastr.error('No PDF URL provided');
    return;
  }
  window.open(url, '_blank');
};

// New function to get currency name based on currency code
export const getCurrency = (currencyCode) => {
  switch (currencyCode) {
    case 1: return 'USD';
    case 2: return 'KHR';
    default: return 'Unknown';
  }
};
