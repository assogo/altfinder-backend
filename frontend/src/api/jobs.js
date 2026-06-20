const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

async function handleResponse(res) {
  if (!res.ok) {
    let message = `Erreur ${res.status}`;
    try {
      const body = await res.json();
      message = body.error || message;
    } catch {
      // réponse non JSON, on garde le message par défaut
    }
    throw new Error(message);
  }
  return res.json();
}

export async function fetchJobs({ keyword = '', location = '', category = '', status = '', page = 1, limit = 20 } = {}) {
  const params = new URLSearchParams();
  if (keyword) params.set('keyword', keyword);
  if (location) params.set('location', location);
  if (category) params.set('category', category);
  if (status) params.set('status', status);
  params.set('page', page);
  params.set('limit', limit);

  const res = await fetch(`${API_URL}/api/jobs?${params.toString()}`);
  return handleResponse(res);
}

export async function fetchJobById(id) {
  const res = await fetch(`${API_URL}/api/jobs/${id}`);
  return handleResponse(res);
}
