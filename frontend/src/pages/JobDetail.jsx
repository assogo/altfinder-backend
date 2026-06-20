import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { fetchJobById } from '../api/jobs';
import CategoryTag from '../components/CategoryTag';
import StatusBadge from '../components/StatusBadge';

function formatDate(iso) {
  if (!iso) return 'Date inconnue';
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
}

export default function JobDetail() {
  const { id } = useParams();
  const [job, setJob] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    fetchJobById(id)
      .then((res) => setJob(res.data))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [id]);

  if (loading) return <div className="page detail-page"><p>Chargement…</p></div>;
  if (error) return <div className="page detail-page"><p className="error-box">{error}</p></div>;
  if (!job) return null;

  return (
    <div className="page detail-page">
      <Link to="/" className="back-link">← Retour aux offres</Link>

      <div className="detail-head">
        <CategoryTag category={job.category} />
        <StatusBadge status={job.status} />
      </div>

      <h1 className="detail-title">{job.title}</h1>
      <p className="detail-meta">
        {job.company ? `${job.company} · ` : ''}{job.location || 'Lieu non précisé'}
      </p>
      <p className="detail-meta detail-meta-soft">
        {job.contractType || 'Contrat non précisé'} · Publiée le {formatDate(job.publishedAt)}
      </p>

      <div className="detail-description">
        {job.description
          ? job.description.split('\n').filter(Boolean).map((para, i) => <p key={i}>{para}</p>)
          : <p>Aucune description fournie.</p>}
      </div>

      {job.url && (
        <a href={job.url} target="_blank" rel="noopener noreferrer" className="cta-button">
          Voir l'offre originale →
        </a>
      )}
    </div>
  );
}
