import java.util.*;
import java.io.*;
import java.math.*;

class Solution {

    public static void main(String args[])
    {
        Scanner in = new Scanner(System.in);
        int N = in.nextInt(); // Number of elements which make up the association table.
        int Q = in.nextInt(); // Number Q of file names to be analyzed.
        String[] exts = new String[N*2];
        
        for (int i = 0; i < N; i++) {
            String EXT = in.next(); // file extension
            String MT = in.next(); // MIME type.
            in.nextLine();
            exts[i*2] = EXT.toLowerCase();
            exts[i*2+1] = MT;
        }
        
        for (int i = 0; i < N; i++) {
            //System.err.println(exts[i*2]);
            //System.err.println(exts[i*2+1]);
        }

        System.err.println(Q + " filenames to check");
        for (int i = 0; i < Q; i++) {
            String FNAME = in.nextLine(); // One file name per line.
            System.err.println("filename: " + FNAME);
            String ext = getExtension(FNAME);
            System.err.println("ext: " + ext);
            if ("" == ext) {
                System.out.println("UNKNOWN");
                continue;
            }

            boolean found = false;
            for (int j = 0; j < N; j++) {
                if (ext.equals(exts[j*2])) {
                    System.out.println(exts[j*2+1]);
                    found = true;
                    break;
                }
            }
            if (false == found) {
                System.out.println("UNKNOWN");
            }
        }
    }

    protected static String getExtension(String filename)
    {
        int pos = filename.lastIndexOf('.');
        if (-1 == pos) {
            return "";
        }

        return filename.substring(pos+1).toLowerCase();
    }
}