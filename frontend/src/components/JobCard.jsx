import { Link } from 'react-router-dom';
import CategoryTag from './CategoryTag';
import StatusBadge from './StatusBadge';

function formatDate(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function JobCard({ job }) {
  return (
    <Link to={`/offres/${job.id}`} className="job-card">
      <div className="job-card-top">
        <CategoryTag category={job.category} />
        <StatusBadge status={job.status} />
      </div>
      <h2 className="job-card-title">{job.title}</h2>
      <p className="job-card-meta">
        {job.company ? `${job.company} · ` : ''}{job.location || 'Lieu non précisé'}
      </p>
      <p className="job-card-foot">
        <span>{job.contractType || 'Contrat non précisé'}</span>
        <span className="job-card-date">{formatDate(job.publishedAt)}</span>
      </p>
    </Link>
  );
}
