  /* ---------- Init ---------- */
  // refreshTabCounts() and initDonut() used to be called here. Both belonged
  // to the approvals queue / SVG donut, neither of which any Procurement view
  // renders, so they were removed together with that markup.
  if(typeof initRowActionButtons === 'function') initRowActionButtons();
  if(typeof animateDashboard === 'function') animateDashboard();
