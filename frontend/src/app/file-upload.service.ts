import { HttpClient, HttpErrorResponse } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { Observable } from "rxjs";
import { catchError, map } from "rxjs/operators";
import { MatSnackBar } from "@angular/material/snack-bar";
import { PdfFile } from "@models/PdfFile";
import { environment } from "environments/environment";

@Injectable({
    providedIn: "root",
})
export class FileUploadService {
    constructor(
        private httpClient: HttpClient,
        private snackBar: MatSnackBar
    ) {}

    public postFile(fileToUpload: File): Observable<PdfFile> {
        const formData: FormData = new FormData();
        formData.append("fileKey", fileToUpload, fileToUpload.name);
        return this.httpClient.post<PdfFile>(
            environment.apiUrl + "/files/upload",
            formData
        );
    }
}
