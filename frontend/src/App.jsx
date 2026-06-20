import { Routes, Route } from 'react-router-dom';
import JobList from './pages/JobList';
import JobDetail from './pages/JobDetail';

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<JobList />} />
      <Route path="/offres/:id" element={<JobDetail />} />
    </Routes>
  );
}
