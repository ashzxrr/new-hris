import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import Riwayat from './components/Riwayat';

function App() {
  const [route, setRoute] = useState(window.location.hash);

  useEffect(() => {
    const onHash = () => setRoute(window.location.hash);
    window.addEventListener('hashchange', onHash);
    return () => window.removeEventListener('hashchange', onHash);
  }, []);

  if (route.startsWith('#/riwayat')) {
    return <Riwayat />;
  }

  return (
    <div style={{ padding: 20 }}>
      <h2>React POC</h2>
      <p>
        <a href="#/riwayat">Open Riwayat</a>
      </p>
    </div>
  );
}

const el = document.getElementById('react-root');
if (el) {
  const root = createRoot(el);
  root.render(<App />);
} else {
  // Helpful hint for Blade integration
  console.warn('No #react-root element found. Add <div id="react-root"></div> to your Blade.');
}
