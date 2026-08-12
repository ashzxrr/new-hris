import React, { useState, useEffect } from 'react';

export default function Riwayat() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [data, setData] = useState([]);

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    fetch('/api/attendance/history')
      .then((res) => {
        if (!res.ok) throw new Error(res.statusText || 'Network error');
        return res.json();
      })
      .then((json) => {
        if (mounted) setData(json.data || json || []);
      })
      .catch((err) => {
        if (mounted) setError(err.message);
      })
      .finally(() => {
        if (mounted) setLoading(false);
      });
    return () => (mounted = false);
  }, []);

  if (loading) return <div>Loading...</div>;
  if (error) return <div style={{ color: 'red' }}>Error: {error}</div>;
  if (!data || data.length === 0) return <div>No history found.</div>;

  return (
    <div style={{ padding: 20 }}>
      <h3>Riwayat Absensi</h3>
      <ul>
        {data.map((item, idx) => (
          <li key={item.id || idx} style={{ marginBottom: 8 }}>
            <strong>{item.date || item.tanggal || item.created_at}</strong>
            {" — "}
            {item.note || item.status || JSON.stringify(item)}
          </li>
        ))}
      </ul>
    </div>
  );
}
