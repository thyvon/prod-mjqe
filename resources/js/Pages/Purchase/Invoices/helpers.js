export const formatCurrency = (value, currency) => {
  const symbol = currency === 1 ? '$' : '៛';
  return `${symbol} ${parseFloat(value).toFixed(2)}`;
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

export const getFileThumbnail = (fileUrl) => {
  const extension = fileUrl.split('.').pop().toLowerCase();

  const thumbnailMap = {
    pdf: '/images/thumbnails-pdf.png',
    doc: '/images/thumbnails-doc.png',
    docx: '/images/thumbnails-doc.png',
    xls: '/images/thumbnails-xls.png',
    xlsx: '/images/thumbnails-xls.png',
    ppt: '/images/thumbnails-ppt.png',
    pptx: '/images/thumbnails-ppt.png',
    jpg: fileUrl,
    jpeg: fileUrl,
    png: fileUrl,
    gif: fileUrl,
    bmp: fileUrl,
    svg: fileUrl,
  };

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
