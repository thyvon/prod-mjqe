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
  const fileExtension = fileUrl.split('.').pop().toLowerCase();
  const imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
  if (imageExtensions.includes(fileExtension)) {
    return fileUrl;
  }
  return '/images/default-file-icon.png'; // Ensure this path is correct and the file exists
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
