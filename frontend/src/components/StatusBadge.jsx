const STATUS = {
  open: { label: 'Active', cls: 'status-open' },
  expired: { label: 'Expirée', cls: 'status-expired' },
  probably_closed: { label: 'Probablement close', cls: 'status-warning' },
};

export default function StatusBadge({ status }) {
  const info = STATUS[status] || { label: status || 'Inconnu', cls: 'status-default' };
  return <span className={`status-badge ${info.cls}`}>{info.label}</span>;
}
