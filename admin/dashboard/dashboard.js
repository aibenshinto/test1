function loadContent(section) {
  let url = '';
  let css = '';
  let js = '';

  switch (section) {
    case 'staff':
      url = '../staff/fetch_staff.php';
      css = '../staff/staff.css';
      js = '../staff/staff.js';
      break;
    case 'product':
      url = '../product/fetch_product.php';
      css = '../product/product.css';
      js = '../product/product.js';
      break;
    case 'orders':
      url = 'fetch_orders.php';
      css = ''; // add if you have CSS file for orders
      js = '';  // add if you have JS file for orders
      break;
    default:
      url = '';
      css = '';
      js = '';
  }

  if (url) {
    // Load CSS dynamically if specified
    if (css) {
      let existingLink = document.querySelector(`link[href="${css}"]`);
      if (!existingLink) {
        let link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = css;
        document.head.appendChild(link);
      }
    }

    fetch(url)
      .then(response => {
        if (!response.ok) throw new Error('Network response was not OK');
        return response.text();
      })
      .then(html => {
        document.getElementById('mainContent').innerHTML = html;

        // Load JS dynamically if specified
        if (js) {
          // Remove existing script with same src if any
          let existingScript = document.querySelector(`script[src="${js}"]`);
          if (existingScript) {
            existingScript.remove();
          }
          let script = document.createElement('script');
          script.src = js;
          script.onload = () => {
            console.log(js + ' loaded');
          };
          document.body.appendChild(script);
        }
      })
      .catch(error => {
        document.getElementById('mainContent').innerHTML = `<p>Error loading content: ${error.message}</p>`;
      });
  }
}

// Load Staff section on page load by default
window.onload = () => {
  const params = new URLSearchParams(window.location.search);
  const section = params.get('load') || 'staff'; // default to staff if no param
  loadContent(section);
};