// Type definitions for LinkedIn scraping
interface JobDetails {
    title: string;
    company: string;
    location: string;
    description: string;
    applyUrl: string;
    postedDate?: string;
    url: string;
}

interface WorkTypeAndSkills {
    workType: string;
    skills: string[];
}

interface JobExtractionResult {
    title: string;
    company: string;
    location: string;
    description: string;
    applyUrl: string;
    workType: string;
    skills: string[];
}

interface PageAnalysis {
    url: string;
    title: string;
    bodyClasses: string;
    mainFound: boolean;
    jobLinksCount: number;
    hasLoginForm: boolean;
    pageText: string;
}

interface ButtonInfo {
    text: string;
    ariaLabel: string;
    className: string;
    id?: string;
}
